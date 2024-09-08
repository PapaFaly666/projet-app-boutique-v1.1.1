<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Mail\ClientQRCodeMail;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;

class ClientObserver
{
    private $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    /**
     * Handle the Client "creating" event.
     */
    public function creating(Client $client)
    {
        $data = request()->all();

        // Validation des données utilisateur
        $validator = Validator::make($data['users'], [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validation de l'image
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $uploadedImage = null;
        $imageUploadFailed = false;

        // Upload de l'image s'il y en a une
        if (isset($data['users']['image']) && $data['users']['image']->isValid()) {
            try {
                $uploadedImage = $this->cloudinary->uploadImage($data['users']['image']);
            } catch (Exception $e) {
                $imageUploadFailed = true;

                // Sauvegarde locale de l'image
                $localPath = $data['users']['image']->store('local_images');
                $uploadedImage = Storage::url($localPath);
            }
        }

        // Créer l'utilisateur associé
        if (isset($data['users'])) {
            $user = new User();
            $user->email = $data['users']['email'];
            $user->password = Hash::make($data['users']['password']);
            $user->role = 'client';
            $user->nom = $data['users']['nom'];
            $user->prenom = $data['users']['prenom'];
            $user->image_url = $uploadedImage;
            $user->client_id = $client->id; // Associer l'utilisateur au client
            $user->image_upload_failed = $imageUploadFailed; // Marquer l'échec de l'upload
            $user->save();

            // Générer le QR code
            try {
                $qrCode = QrCode::format('png')->size(200)->generate($client->telephone);
                $qrCodeBase64 = base64_encode($qrCode);
            } catch (Exception $e) {
                throw new \Exception('Erreur lors de la génération du QR code : ' . $e->getMessage());
            }

            // Envoyer le QR code par e-mail
            try {
                Mail::to($user->email)->send(new ClientQRCodeMail($user, $qrCodeBase64));
            } catch (Exception $e) {
                throw new \Exception('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
            }
        }
    }
}
