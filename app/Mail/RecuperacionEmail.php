<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecuperacionEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $infoCorreo = array();

    /** Crear una instancia de objeto de correo.
     * @var array<string, string> Arreglo de valores para agregar en el contenido del correo */
    public function __construct($conteCorreo){
        $this->infoCorreo = $conteCorreo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope{
        if(array_key_exists('nombre', $this->infoCorreo)){
            return new Envelope(
                subject: 'Recuperación de Acceso para '.$this->infoCorreo['nombre'].' '.$this->infoCorreo['apePat'].' '.$this->infoCorreo['apeMat'].' de Building Continuity'
            );
        }
        
        return new Envelope(
            subject: 'Recuperación de Acceso para Building Continuity'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.recuAcc',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
