<?php
class Upload_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    public function upload_images($path = null)
    {

        if (!$path) $uploadPath = './assets/uploads/';
        if ($path)   $uploadPath = './assets/' . $path . '/';
        $uploadedFiles = [];
        if (!empty($_FILES['images']['name'])) {
            // เช็กว่าไฟล์อัปโหลดไปที่ assets จริงไหม
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            foreach ($_FILES['images']['name'] as $key => $name) {
                $tmpName = $_FILES['images']['tmp_name'][$key];
                $newFileName = time() . '_' . $name;
                $destination = $uploadPath . $newFileName;
                if (move_uploaded_file($tmpName, $destination)) {
                    $uploadedFiles[] = $uploadPath . $newFileName;
                }
            }
        }

        return  $uploadedFiles;
    }
}
