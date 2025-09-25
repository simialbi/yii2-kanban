<?php

namespace simialbi\yii2\kanban\helpers;

class FileHelper extends \yii\helpers\FileHelper
{
    /**
     * Checks if a file already exists. if so, renames the file:
     * test.png -> test_1.png
     * test_1.png -> test_2.png
     * ...
     *
     * Use this method to rename an uploaded file if another with the same name in the folder already exists
     *
     * @param string|UploadedFile $file A filename or an UploadedFile instance
     * @param string $destination The folder in which the file should be saved
     * @return void
     */
    public static function renameFileIfExists(UploadedFile|string &$file, string $destination): void
    {
        if (!file_exists($destination)) {
            throw new InvalidArgumentException('Path ' . $destination . ' does not exist!');
        }

        if ($file instanceof UploadedFile) {
            $origName = $file->baseName;
            $extension = $file->extension;
        } else {
            $arrPathParts = pathinfo($file);
            $origName = $arrPathParts['filename'];
            $extension = $arrPathParts['extension'];
        }
        $newName = $origName . '.' . $extension;


        $index = 1;
        while (file_exists(static::normalizePath($destination . DIRECTORY_SEPARATOR . $newName))) {
            $newName = $origName . '_' . $index . '.' . $extension;
            $index++;
        }

        if ($file instanceof UploadedFile) {
            $file->name = $newName;
        } else {
            $file = $newName;
        }
    }
}
