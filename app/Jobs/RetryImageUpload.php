<?php


namespace App\Jobs;

use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class RetryImageUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $cloudinary;

    public function __construct(User $user, CloudinaryService $cloudinary)
    {
        $this->user = $user;
        $this->cloudinary = $cloudinary;
    }

    public function handle()
    {
        if ($this->user->image_upload_failed) {
            try {
                // Charger l'image locale vers Cloudinary
                $localPath = storage_path('app' . parse_url($this->user->image_url, PHP_URL_PATH));
                $uploadedImage = $this->cloudinary->uploadImage(new \Illuminate\Http\UploadedFile($localPath, 'image.jpg'));

                // Mettre à jour l'URL de l'image et réinitialiser le flag d'échec
                $this->user->image_url = $uploadedImage;
                $this->user->image_upload_failed = false;
                $this->user->save();
            } catch (Exception $e) {
                // Gestion des erreurs de réupload
            }
        }
    }
}
