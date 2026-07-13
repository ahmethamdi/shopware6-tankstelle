<?php declare(strict_types=1);

namespace Tankstellenpartner24Theme\Subscriber;

use Shopware\Core\Framework\Adapter\Cache\Event\HttpCacheCookieEvent;
use Shopware\Core\Framework\Adapter\Cache\Http\Extension\CacheHashRequiredExtension;
use Shopware\Core\Framework\Extensions\ExtensionDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tankstellenpartner24Theme — Vertriebler (Agent) HTTP-Cache-Hash Fix.
 *
 * PROBLEM (Mike): Als Vertriebler/Agent eingeloggt → Startseite zeigt keine
 * Preise, Header-Menü teils weg. Cache löschen → Preise da, bleiben aber für
 * ALLE (auch nach Logout) sichtbar. Login-State landet im geteilten Cache.
 * Normale Kunden sind NICHT betroffen.
 *
 * URSACHE (Shopware 6.7, neues Cache-System CacheHeadersService):
 *  1) isCacheHashRequired() liefert für den Agent FALSE, weil es nur
 *     $context->getCustomer() !== null prüft. Der Agent hat keinen customer
 *     (nur die 'vioAgent'-Extension) → es wird KEIN sw-cache-hash-Cookie gesetzt
 *     → der Agent teilt sich den anonymen Cache-Bucket.
 *  2) buildCacheHash() setzt LOGGED_IN_STATE = getCustomer() ? 'logged-in'
 *     : 'not-logged-in' → der Agent bekäme denselben Hash wie ein anonymer
 *     Besucher, selbst wenn der Hash erzwungen würde.
 *
 * Vio setzt zwar $context->addState('logged-in') (KernelRoutingSubscriber),
 * aber das wirkt nur auf das ALTE sw-states-System, NICHT auf den neuen
 * CacheHeadersService-Hash → reicht in 6.7 nicht.
 *
 * LÖSUNG (Shopware-konform, KEINE Session-Daten im Cache, nur Cache-KEY-Trennung
 * — genau Mikes Regel):
 *  A) cache-hash.required.pre: Wenn ein Agent eingeloggt ist, Cache-Hash
 *     erzwingen (result=true) → eigener sw-cache-hash-Cookie wird gesetzt.
 *  B) HttpCacheCookieEvent: LOGGED_IN_STATE für den Agent auf 'agent-logged-in'
 *     setzen → eigener, vom anonymen Bucket getrennter Hash.
 *
 * Ergebnis: Agent = eigener Cache-Bucket (mit Preisen), Anonym = eigener Bucket
 * (ohne Preise). Kein Vermischen, nach Logout fällt der Besucher auf den
 * anonymen Bucket zurück. Greift ausschließlich im Agent-Modus.
 */
class AgentCacheHashSubscriber implements EventSubscriberInterface
{
    private const AGENT_KEY = 'vioAgent';
    private const AGENT_STATE = 'agent-logged-in';

    public static function getSubscribedEvents(): array
    {
        return [
            // A) Cache-Hash für eingeloggte Agenten erzwingen.
            ExtensionDispatcher::pre(CacheHashRequiredExtension::NAME) => 'forceCacheHashForAgent',
            // B) Logged-in-State des Agenten vom anonymen Bucket trennen.
            HttpCacheCookieEvent::class => 'splitAgentCacheBucket',
        ];
    }

    public function forceCacheHashForAgent(CacheHashRequiredExtension $extension): void
    {
        // Nur Agent (kein customer, aber vioAgent-Extension).
        if ($extension->salesChannelContext->getCustomer() !== null) {
            return;
        }

        if (!$extension->salesChannelContext->hasExtension(self::AGENT_KEY)) {
            return;
        }

        // Cache-Hash erzwingen → eigener sw-cache-hash-Cookie für den Agent.
        $extension->result = true;
        $extension->stopPropagation();
    }

    public function splitAgentCacheBucket(HttpCacheCookieEvent $event): void
    {
        if ($event->context->getCustomer() !== null) {
            return;
        }

        if (!$event->context->hasExtension(self::AGENT_KEY)) {
            return;
        }

        // Eigenen logged-in-State für den Agent → getrennter Hash vom Anonymen.
        $event->add(HttpCacheCookieEvent::LOGGED_IN_STATE, self::AGENT_STATE);
    }
}
