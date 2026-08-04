<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMail7Bundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\DoNotContact;
use MauticPlugin\MauticMail7Bundle\Helper\Mail7Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Validates a contact's email through Mail7 after it is saved and, when the address is
 * Not Valid, marks the contact as Do Not Contact (email channel) so campaigns skip it.
 *
 * Honest by design: only Not Valid addresses are acted on; Unknown addresses (catch-all,
 * greylisting, disposable) are left contactable unless mail7_block_unknown is enabled.
 * Fails open - a Mail7 hiccup never affects the save.
 */
class LeadSubscriber implements EventSubscriberInterface
{
    private CoreParametersHelper $params;

    private DoNotContact $doNotContact;

    private LoggerInterface $logger;

    public function __construct(CoreParametersHelper $params, DoNotContact $doNotContact, LoggerInterface $logger)
    {
        $this->params       = $params;
        $this->doNotContact = $doNotContact;
        $this->logger       = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_POST_SAVE => ['onLeadPostSave', 0],
        ];
    }

    public function onLeadPostSave(LeadEvent $event): void
    {
        try {
            $lead = $event->getLead();
            if (null === $lead || !$lead->getId()) {
                return;
            }

            $email = trim((string) $lead->getEmail());
            if ('' === $email) {
                return;
            }

            $baseUrl = (string) $this->params->get('mail7_base_url', 'https://mail7.net/api');
            if ('' === $baseUrl) {
                $baseUrl = 'https://mail7.net/api';
            }

            $client = new Mail7Client((string) $this->params->get('mail7_api_key', ''), $baseUrl);
            $result = $client->validate($email);
            $status = isset($result['status']) ? (string) $result['status'] : '';

            $blockUnknown = (bool) $this->params->get('mail7_block_unknown', false);
            $shouldBlock  = ('Not Valid' === $status) || ('Unknown' === $status && $blockUnknown);

            if (!$shouldBlock) {
                return; // Valid, Unknown (kept), or an error (fail open) - leave contactable.
            }

            $this->doNotContact->addDncForContact(
                $lead->getId(),
                'email',
                DNC::UNSUBSCRIBED,
                'Mail7: address not deliverable (' . $status . ')'
            );
        } catch (\Throwable $e) {
            // Never let validation affect the contact save.
            $this->logger->warning('Mail7 validation skipped: ' . $e->getMessage());
        }
    }
}
