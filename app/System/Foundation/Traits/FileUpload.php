<?php

namespace App\System\Foundation\Traits;

use App\Exceptions\FileNotFoundException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

trait FileUpload
{
    /**
     * @param Request $request
     * @param string $storePath
     * @param bool $public
     * @param string $fileKey
     * @return array
     */
    public function multipleUpload(Request $request, string $storePath = "", bool $public = true, string $fileKey = "files"): array
    {
        $files = $request->file($fileKey);
        $result = [];
        foreach ($files as $file) {
            $result[] = $this->uploadFile($file, $storePath, $public);
        }

        return $result;
    }

    /**
     * @param UploadedFile $file
     * @param string $storePath
     * @param bool $public
     * @return array
     */
    public function uploadFile(UploadedFile $file, string $storePath = '', bool $public = true): array
    {
        $mimeType = $file->getClientMimeType();
        if (!$storePath) {
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/png':
                case 'image/bmp':
                case 'image/gif':
                    $storePath = config('fileupload.image_directory');
                    break;
                default:
                    $storePath = config('fileupload.document_directory');
                    break;
            }
        }
        if ($public) {
            $path = $file->storePublicly($storePath);
        } else {
            $path = $file->store($storePath);
        }

        return [
            'name' => $file->getClientOriginalName(),
            'path' => Storage::url($path),
            'mime_type' => $mimeType,
        ];
    }

    /**
     * @throws Exception
     */
    public function generateThumbnail(string $url): string
    {
        try {
            if (config('app.docker')) {
                $path = parse_url($url)['path'];
                $content = file_get_contents(ltrim($path, '/'));
            } else {
                $content = file_get_contents($url);
            }
            $image = ImageManager::gd()->read($content);
            $width = config('fileupload.thumb_width');
            $thumb = $image->resizeDown($width, ($image->height() / $image->width()) * $width);

            $storePath = config('fileupload.image_thumb_directory') . '/' . $this->get_filename_from_url($url, config('fileupload.thumb_suffix'));

            Storage::put($storePath, $thumb->encode());
            return Storage::url($storePath);
        } catch (\ErrorException $e) {
            throw new FileNotFoundException();
        } catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * @param string $url
     * @param string $suffixFileName
     * @return string
     */
    private function get_filename_from_url(string $url, string $suffixFileName = ''): string
    {
        $parsedUrl = parse_url($url);
        $pathArr = explode('/', $parsedUrl['path']);
        $filename = array_pop($pathArr);
        $filenameArr = explode('.', $filename);
        $extension = array_pop($filenameArr);
        $filename = implode('.', $filenameArr);
        if ($suffixFileName) {
            $filename .= $suffixFileName;
        }
        $filename .= "." . $extension;

        return $filename;
    }
}
