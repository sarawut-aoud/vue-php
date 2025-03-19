<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class Products extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Upload_model', 'Upload');

        // $this->validateAuth();
    }
    public function index_get()
    {
        try {
            self::setRes(['msg' => 'Products API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getList_get($id = null)
    {
        try {
            $req = (object) $this->input->get();

            $filter = '';
            if ($req->cate) {
                $filter .= " AND b.cate_id = $req->cate";
            }
            if ($req->productId) {
                $filter .= " AND a.id = $req->productId";
            }
            if ($id) {
                $filter .= " AND a.id = $id";
            }
            $result = $this->db->query(
                "SELECT a.* 
                FROM swtar_reread.products a
                LEFT JOIN swtar_reread.products_cate b ON b.p_id = a.id
                WHERE a.is_active = 1 $filter
                GROUP BY a.id
          "
            )->result();

            $result = array_map(function ($e) {
                return [
                    '_i' => (int) $e->id,
                    'no' => $e->p_no,
                    'name' => $e->p_name,
                    'detail' => $e->p_detail,
                    'price' => (float)$e->p_price,
                    'sold_out' => (bool)$e->so_out,
                    'cate_id' => array_map(function ($e) {
                        return [
                            '_i' => (int) $e->id,
                            'name' => $e->cate_name,
                        ];
                    }, $this->db->query("SELECT a.*
                    FROM swtar_reread.category a 
                    LEFT JOIN swtar_reread.products_cate b ON b.cate_id = a.id
                    WHERE b.p_id = ?", [$e->id])->result()),
                    'picture' => array_map(function ($e) {
                        return [
                            '_i' => (int) $e->id,
                            'path' => '/api' . substr($e->picture, 1),
                        ];
                    }, $this->db->query("SELECT a.*
                    FROM swtar_reread.product_image a 
                    WHERE a.p_id = ?", [$e->id])->result()),
                    'path_group' => array_map(function ($e) {
                        return '/api' . ($e->picture);
                    }, $this->db->query("SELECT a.*
                    FROM swtar_reread.product_image a 
                    WHERE a.p_id = ?", [$e->id])->result()),
                ];
            }, $result);


            if (!!$id)  self::setRes($result[0], 200);

            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function create_post()
    {
        $this->validateAuth();

        try {
            $req = (object) $this->input->post();
            if ($req->detail == 'null') $req->detail = NULL;
            $this->db->insert('swtar_reread.products', [
                'p_no' => $req->no,
                'p_name' => $req->name,
                'p_detail' => $req->detail ?? null,
                'p_price' => $req->price,
                'is_active' => 1,
            ]);
            $last_id = $this->db->insert_id();

            $cate_data = [];
            $cate_id = explode(',', $req->cate_id);
            if (count($cate_id) > 0) {
                foreach ($cate_id  as $id) {
                    $cate_data[] = [
                        'p_id' => $last_id,
                        'cate_id' => $id,
                    ];
                }
                if (count($cate_data) > 0) $this->db->insert_batch('swtar_reread.products_cate', $cate_data);
            }
            $files = $this->Upload->upload_images('uploads');


            $file_image = [];
            if (count($files) > 0) {
                foreach ($files as $path) {
                    $file_image[] = [
                        'p_id' => $last_id,
                        'picture' =>  $path

                    ];
                }
                if (count($file_image) > 0) $this->db->insert_batch('swtar_reread.product_image', $file_image);
            }


            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function update_post()
    {
        $this->validateAuth();

        try {
            $req = (object) $this->input->post();
            if ($req->detail == 'null') $req->detail = NULL;
            if (!$req->p_id) self::setErr('NOT FOUND', 404);
            $exit = $this->db->get_where("swtar_reread.products", ['id' => $req->p_id])->row();
            if (!$exit) self::setErr('NOT FOUND', 404);

            $this->db->update('swtar_reread.products', [
                'p_no' => $req->no,
                'p_name' => $req->name,
                'p_detail' => $req->detail ?? null,
                'p_price' => $req->price,
            ], [
                'id' => $req->p_id
            ]);


            $cate_id = explode(',', $req->cate_id);
            if (count($cate_id) > 0) {
                foreach ($cate_id  as $id) {
                    $this->db->update('swtar_reread.products_cate', ['cate_id' => $id], ['p_id' => $req->p_id]);
                }
            }
            $files = $this->Upload->upload_images('uploads');


            if (count($files) > 0) {
                foreach ($files as $path) {
                    $this->db->update('swtar_reread.product_image', ['picture' => $path], ['p_id' => $req->p_id]);
                }
            }


            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function remove_post($id)
    {
        try {
            if (!$id) self::setRes("INPUT ERROR", 401);
            $exit = $this->db->query("SELECT * FROM swtar_reread.products WHERE id =?", [$id])->row();
            if (!$exit) self::setRes("NOT FOUNDs", 401);
            $this->db->update('swtar_reread.products', ['is_active' => 0], ['id' => $id]);
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function updateSoldOut_post($id)
    {
        try {
            if (!$id) self::setRes("INPUT ERROR", 401);
            $exit = $this->db->query("SELECT * FROM swtar_reread.products WHERE id =?", [$id])->row();
            if (!$exit) self::setRes("NOT FOUNDs", 401);
            $item =  $exit->so_out == 1 ? 0 : 1;
            $this->db->update('swtar_reread.products', ['so_out' =>  $item], ['id' => $id]);
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }

    public function removePicture_get($id)
    {
        try {
            if (!$id) self::setRes("INPUT ERROR", 401);
            $exit = $this->db->query("SELECT * FROM swtar_reread.product_image WHERE id =?", [$id])->row();
            if (!$exit) self::setRes("NOT FOUNDS", 401);
            if (is_dir(FCPATH . $exit->picture)) {
                unlink(FCPATH . $exit->picture);
            }
            $this->db->delete('swtar_reread.product_image', ['id' => $id]);
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function uploadImage_post()
    {
        try {
            $req = (object) $this->input->post();
            $files = $this->Upload->upload_images('uploads');

            $file_image = [];
            if (count($files) > 0) {
                foreach ($files as $path) {
                    $file_image[] = [
                        'p_id' =>  $req->id,
                        'picture' =>  $path

                    ];
                }
                if (count($file_image) > 0) $this->db->insert_batch('swtar_reread.product_image', $file_image);
            }
            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
