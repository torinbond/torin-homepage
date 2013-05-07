<?php
/**
 * Folder
 * API for interacting with folders (directories) on the server
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2013 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
use Symfony\Component\Finder\Finder as Finder;
use FilesystemIterator as fIterator;

class Folder
{
    /**
     * Create a new directory.
     *
     * @param  string  $path
     * @param  int     $chmod
     * @return bool
     */
    public static function make($path, $chmod = 0777)
    {
        return ( ! is_dir($path)) ? mkdir($path, $chmod, TRUE) : TRUE;
    }


    /**
     * Move a directory from one location to another.
     *
     * @param  string  $source
     * @param  string  $destination
     * @param  int     $options
     * @return boolean
     */
    public static function move($source, $destination, $options = fIterator::SKIP_DOTS)
    {
        return self::copy($source, $destination, TRUE, $options);
    }


    /**
     * Recursively copy directory contents to another directory.
     *
     * @param  string  $source
     * @param  string  $destination
     * @param  bool    $delete
     * @param  int     $options
     * @return bool
     */
    public static function copy($source, $destination, $delete = FALSE, $options = fIterator::SKIP_DOTS)
    {
        if ( ! is_dir($source)) return FALSE;

        // First we need to create the destination directory if it doesn't
        // already exists. This directory hosts all of the assets we copy
        // from the installed bundle's source directory.
        if ( ! is_dir($destination))
        {
            mkdir($destination, 0777, TRUE);
        }

        $items = new fIterator($source, $options);

        foreach ($items as $item)
        {
            $location = $destination.DIRECTORY_SEPARATOR.$item->getBasename();

            // If the file system item is a directory, we will recurse the
            // function, passing in the item directory. To get the proper
            // destination path, we'll add the basename of the source to
            // to the destination directory.
            if ($item->isDir())
            {
                $path = $item->getRealPath();

                if (! static::copy($path, $location, $delete, $options)) return FALSE;

                if ($delete) @rmdir($item->getRealPath());
            }
            // If the file system item is an actual file, we can copy the
            // file from the bundle asset directory to the public asset
            // directory. The "copy" method will overwrite any existing
            // files with the same name.
            else
            {
                if(! copy($item->getRealPath(), $location)) return FALSE;

                if ($delete) @unlink($item->getRealPath());
            }
        }

        unset($items);
        if ($delete) @rmdir($source);

        return TRUE;
    }


    /**
     * Recursively delete a directory.
     *
     * @param  string  $directory
     * @param  bool    $preserve
     * @return void
     */
    public static function delete($directory, $preserve = FALSE)
    {
        if ( ! is_dir($directory)) return;

        $items = new fIterator($directory);

        foreach ($items as $item)
        {
            // If the item is a directory, we can just recurse into the
            // function and delete that sub-directory, otherwise we'll
            // just delete the file and keep going!
            if ($item->isDir())
            {
                static::delete($item->getRealPath());
            }
            else
            {
                @unlink($item->getRealPath());
            }
        }

        unset($items);
        if ( ! $preserve) @rmdir($directory);
    }


    /**
     * Empty the specified directory of all files and folders.
     *
     * @param  string  $directory
     * @return void
     */
    public static function wipe($directory)
    {
        self::delete($directory, TRUE);
    }


    /**
     * Get the most recently modified file in a directory.
     *
     * @param  string       $directory
     * @param  int          $options
     * @return SplFileInfo
     */
    public static function latest($directory, $options = fIterator::SKIP_DOTS)
    {
        $latest = NULL;

        $time = 0;

        $items = new fIterator($directory, $options);

        // To get the latest created file, we'll simply loop through the
        // directory, setting the latest file if we encounter a file
        // with a UNIX timestamp greater than the latest one.
        foreach ($items as $item)
        {
            if ($item->getMTime() > $time)
            {
                $latest = $item;
                $time = $item->getMTime();
            }
        }

        return $latest;
    }


    /**
     * Checks to see if a given $folder is writable
     *
     * @param string  $folder  Folder to check
     * @return bool
     */
    public static function isWritable($folder)
    {
        return self::exists($folder) && is_writable($folder);
    }


    /**
     * Checks to see if a given $folder exists
     *
     * @param string  $folder  Folder to check
     * @return bool
     */
    public static function exists($folder)
    {
        return is_dir($folder);
    }
}