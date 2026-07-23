<?php

declare(strict_types=1);

namespace App\Support\Mail;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends application email on the platform's configured transport, as the
 * sending tenant's identity.
 *
 * At send time this resolves the enabled transport (Resend / Postmark / SES /
 * SMTP / custom) and the tenant's active alias, configures a one-off Laravel
 * SMTP mailer from that, sends the message, and records the outcome in
 * `email_send_logs`. Callers never touch provider secrets or config.
 *
 * This is the email counterpart to the platform's AI provider wiring: the owner
 * configures the transport once; every tenant sends through it as itself.
 */
final class TenantMailer
{
    /** The runtime mailer key this service configures on each send. */
    private const MAILER = 'bespoke_runtime';

    public function __construct(
        private readonly EmailTransportResolver $resolver,
        private readonly EmailLogWriter $log,
        private readonly Config $config,
    ) {
    }

    /**
     * Send an HTML message. Returns [ok, message] — ok=false when no transport is
     * configured or the send failed (the reason is in message). Never throws.
     *
     * @return array{0:bool,1:string}
     */
    public function send(
        string $toEmail,
        string $subject,
        string $html,
        ?string $organizationId = null,
        ?string $feature = null,
    ): array {
        $resolved = $this->resolver->resolve($organizationId);

        if ($resolved === null) {
            return [false, 'No email provider is enabled and configured. Enable one on Platform → Email Integration first.'];
        }

        // Install the resolved config as a one-off mailer and rebuild it so the
        // new settings take effect (the mail manager caches built mailers).
        $this->config->set('mail.mailers.'.self::MAILER, $resolved['config']);
        Mail::purge(self::MAILER);

        try {
            Mail::mailer(self::MAILER)->html($html, function (Message $message) use ($toEmail, $subject, $resolved): void {
                $message->to($toEmail)->subject($subject);

                if ($resolved['from_address'] !== null) {
                    $message->from($resolved['from_address'], $resolved['from_name']);
                }
                if ($resolved['reply_to'] !== null) {
                    $message->replyTo($resolved['reply_to']);
                }
            });
        } catch (Throwable $e) {
            report($e);
            $this->log->record(
                $resolved['integration_id'],
                $organizationId,
                $feature,
                'failed',
                $toEmail,
                null,
                ['provider' => $resolved['provider'], 'error' => $e->getMessage()],
            );

            return [false, 'The email could not be sent: '.$e->getMessage()];
        }

        $this->log->record(
            $resolved['integration_id'],
            $organizationId,
            $feature,
            'sent',
            $toEmail,
            null,
            ['provider' => $resolved['provider']],
        );

        return [true, 'Email sent via '.$resolved['provider'].'.'];
    }

    /**
     * Send a fixed diagnostic message to prove the transport works end to end.
     *
     * @return array{0:bool,1:string}
     */
    public function sendTest(string $toEmail, ?string $organizationId = null): array
    {
        $html = '<p>This is a test message from BespokeLMS.</p>'
            .'<p>If you are reading this, the platform email transport is configured correctly.</p>';

        return $this->send($toEmail, 'BespokeLMS — email transport test', $html, $organizationId, 'test');
    }
}
