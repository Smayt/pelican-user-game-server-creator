<?php
namespace Smayt\UserGameServerCreator\Services;

use App\Models\Egg;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smayt\UserGameServerCreator\Models\UgscEggImage;

class UgscImageService
{
    private string $disk = 'public';
    private string $basePath = 'ugsc/eggs';

    public function fetchAll(Egg $egg, int $appId): array
    {
        return [
            'grid'   => $this->fetchGrid($egg, $appId),
            'banner' => $this->fetchBanner($egg, $appId),
            'list'   => $this->fetchList($egg, $appId),
        ];
    }

    public function fetchGrid(Egg $egg, int $appId, bool $force = false): bool
    {
        $record = UgscEggImage::forEgg($egg->id);
        if (!$force && $record?->isProtected('grid')) {
            return false;
        }
        $url = "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/library_600x900.jpg";
        return $this->fetchAndStore($egg, $appId, $url, 'grid');
    }

    public function fetchBanner(Egg $egg, int $appId, bool $force = false): bool
    {
        $record = UgscEggImage::forEgg($egg->id);
        if (!$force && $record?->isProtected('banner')) {
            return false;
        }
        $urls = [
            "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/library_hero.jpg",
            "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/header.jpg",
        ];
        foreach ($urls as $url) {
            if ($this->fetchAndStore($egg, $appId, $url, 'banner')) {
                return true;
            }
        }
        return false;
    }

    public function fetchList(Egg $egg, int $appId, bool $force = false): bool
    {
        $record = UgscEggImage::forEgg($egg->id);
        if (!$force && $record?->isProtected('list')) {
            return false;
        }
        $url = "https://cdn.cloudflare.steamstatic.com/steam/apps/{$appId}/capsule_sm_120.jpg";
        return $this->fetchAndStore($egg, $appId, $url, 'list');
    }

    public function uploadImage(Egg $egg, string|UploadedFile $file, string $type): bool
    {
        try {
            $path = "{$this->basePath}/{$egg->uuid}_{$type}.jpg";
            if (is_string($file)) {
                // Filament FileUpload returns a filename stored in livewire-tmp
                $tmpPath = "livewire-tmp/{$file}";
                if (!Storage::disk('local')->exists($tmpPath)) {
                    // try without prefix
                    $tmpPath = $file;
                }
                $contents = Storage::disk('local')->get($tmpPath);
            } else {
                $contents = file_get_contents($file->getRealPath());
            }
            Storage::disk($this->disk)->put($path, $contents);

            $record = UgscEggImage::forEggOrNew($egg->id);
            $record->{"{$type}_path"} = $path;
            $record->{"{$type}_protected"} = true;
            $record->save();

            return true;
        } catch (Exception $e) {
            Log::warning("ugsc-images: Failed to upload {$type} for egg {$egg->id}: " . $e->getMessage());
            return false;
        }
    }

    private function fetchAndStore(Egg $egg, int $appId, string $url, string $type): bool
    {
        try {
            $response = Http::timeout(10)->get($url);
            if (!$response->successful()) {
                return false;
            }

            $path = "{$this->basePath}/{$egg->uuid}_{$type}.jpg";
            Storage::disk($this->disk)->put($path, $response->body());

            $record = UgscEggImage::forEggOrNew($egg->id);
            $record->steam_app_id = $appId;
            $record->{"{$type}_path"} = $path;
            $record->save();

            return true;
        } catch (Exception $e) {
            Log::warning("ugsc-images: Failed to fetch {$type} for egg {$egg->id}: " . $e->getMessage());
            return false;
        }
    }

    public function clearType(Egg $egg, string $type, bool $clearProtection = true): void
    {
        $record = UgscEggImage::forEgg($egg->id);
        if (!$record) return;

        $path = "{$this->basePath}/{$egg->uuid}_{$type}.jpg";
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }

        $record->{"{$type}_path"} = null;
        if ($clearProtection) {
            $record->{"{$type}_protected"} = false;
        }
        $record->save();
    }

    public function clearAll(Egg $egg): void
    {
        $record = UgscEggImage::forEgg($egg->id);
        if (!$record) return;

        foreach (['grid', 'banner', 'list'] as $type) {
            $path = "{$this->basePath}/{$egg->uuid}_{$type}.jpg";
            if (Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);
            }
        }

        $record->delete();
    }

    public function searchSteamAppId(string $name): ?int
    {
        try {
            $response = Http::timeout(10)->get('https://store.steampowered.com/api/storesearch/', [
                'term' => $name,
                'l'    => 'english',
                'cc'   => 'US',
            ]);

            if (!$response->successful()) return null;
            $items = $response->json('items', []);
            if (empty($items)) return null;
            return $items[0]['id'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getImages(Egg $egg): ?UgscEggImage
    {
        return UgscEggImage::forEgg($egg->id);
    }
}
