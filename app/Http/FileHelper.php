<?php

namespace App\Http;

use App\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class FileHelper
{
    /**
     * @var UploadedFile
     */
    private $_file;

    /**
     * @var string
     */
    private $_name;

    /**
     * @var string
     */
    private $_ext;

    /**
     * @var int
     */
    private $_size;

    public function __construct(UploadedFile $file)
    {
        $this->_file = $file;
        $this->_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $this->_ext = $file->extension();
        $this->_size = $file->getSize();
    }

    /**
     * @return File
     */
    public function fill(): File
    {
        $file = new File;
        $file->user_id = Auth::user()->id;
        $file->name = $this->_name;
        $file->ext = $this->_ext;
        $file->size = $this->_size;

        return $file;
    }

    /**
     * @param  string  $folder
     * @return false|string
     */
    public function store($folder)
    {
        $md5 = md5($this->_name);
        $f = substr($md5, 0, 3);
        $s = substr($md5, 3, 3);

        return $this->_file->storeAs(
            $folder . '/' . $f . '/' . $s,
            str_replace('.', '_', uniqid('', true)) . '.' . $this->_ext
        );
    }
}
