<?php

namespace App\Notifications;

use App\Models\PointMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MovimientoPuntosCreado extends Notification
{
    use Queueable;

    public function __construct(public PointMovement $movimiento) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->movimiento;

        $tipoTexto = match ($m->type) {
            'earn'   => 'Carga de puntos',
            'redeem' => 'Canje de puntos',
            'adjust' => 'Ajuste de puntos',
            'expire' => 'Vencimiento de puntos',
            default  => 'Movimiento de puntos',
        };

        $pts = (int) ($m->points ?? 0);
        $ptsTexto = $pts >= 0 ? ('+' . number_format($pts)) : ('-' . number_format(abs($pts)));

        return (new MailMessage)
            ->subject('MisPuntos - ' . $tipoTexto)
            ->greeting('Hola ' . ($notifiable->name ?? ''))
            ->line('Se registró un movimiento en tu cuenta de puntos.')
            ->line('Tipo: ' . $tipoTexto)
            ->line('Puntos: ' . $ptsTexto)
            ->line('Fecha: ' . optional($m->occurred_at)->format('d/m/Y H:i'))
            ->when(!empty($m->reference), fn ($msg) => $msg->line('Referencia: ' . $m->reference))
            ->when(!empty($m->note), fn ($msg) => $msg->line('Detalle: ' . $m->note))
            ->action('Ver mis movimientos', route('points.index'))
            ->salutation('Saludos, MisPuntos');
    }
}
