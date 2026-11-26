<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeUserNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ?string $plainPassword = null,
        public string $panel = 'member',
        public ?string $language = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $lang = $this->language ?? ($notifiable->preferred_language ?? 'en');
        $loginUrl = $this->panel === 'admin' ? url('/admin/login') : url('/member/login');
        $appName = config('app.name', 'MillyFit');

        if ($lang === 'es') {
            $mail = (new MailMessage)
                ->subject("¡Bienvenido(a) a {$appName}! Tus datos de acceso")
                ->greeting("¡Hola {$notifiable->name}!")
                ->line($this->panel === 'admin'
                    ? "Tu cuenta de administrador en **{$appName}** ha sido creada exitosamente."
                    : "¡Gracias por unirte a **{$appName}**! Tu cuenta ha sido creada exitosamente.")
                ->line('A continuación encontrarás tus datos de acceso:')
                ->line("**Correo electrónico:** {$notifiable->email}");

            if (filled($this->plainPassword)) {
                $mail->line("**Contraseña:** `{$this->plainPassword}`");
            }

            return $mail
                ->action('Iniciar Sesión', $loginUrl)
                ->line('Por seguridad, te recomendamos actualizar tu contraseña desde la sección "My profile" después de iniciar sesión.')
                ->salutation("Saludos,\nEl equipo de {$appName}");
        }

        $mail = (new MailMessage)
            ->subject("Welcome to {$appName}! Your Login Credentials")
            ->greeting("Hello {$notifiable->name}!")
            ->line($this->panel === 'admin'
                ? "Your administrator account at **{$appName}** has been created successfully."
                : "Thank you for joining **{$appName}**! Your account has been created successfully.")
            ->line('Here are your login credentials:')
            ->line("**Email:** {$notifiable->email}");

        if (filled($this->plainPassword)) {
            $mail->line("**Password:** `{$this->plainPassword}`");
        }

        return $mail
            ->action('Log In to Your Account', $loginUrl)
            ->line('For your security, we recommend updating your password in the "My profile" section after logging in.')
            ->salutation("Best regards,\nThe {$appName} Team");
    }
}
