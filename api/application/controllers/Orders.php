<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require APPPATH . 'libraries/RestAPI.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class Orders extends RestAPI
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Upload_model', 'Upload');
        $this->validateAuth();
    }
    public function index_get()
    {
        try {

            self::setRes(['msg' => 'Orders API'], 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getMyCart_get()
    {
        try {
            $req = (object) $this->input->get();
            if (!$req->uid) self::setRes("INPUT NOT FOUND", 401);
            $person = $this->db->query("SELECT * FROM swtar_reread.personaldocument WHERE pd_id = ?", [$req->uid])->row();
            if (!$person) self::setRes("NOT FOUND", 400);

            $result = $this->db->query("SELECT * FROM swtar_reread.orders WHERE  pd_id =? AND status='pending' ", [$req->uid])->result();

            $result = array_map(function ($e) {
                return [
                    '_i' => (int) $e->id,
                    'amount' => (int) $e->amount,
                    'vat'  => (float)($e->vat),
                    'discount' => (float) $e->discount,
                    'total' => (float)($e->total_price),
                    'sum' => (float)($e->grand_total),
                    'product' => (object)[
                        'name' => $this->db->get_where('swtar_reread.products', ['id' => $e->product_id])->row('p_name'),
                        'picture' =>  array_map(function ($e) {
                            return [
                                '_i' => (int) $e->id,
                                'path' => '/api' . substr($e->picture, 1),
                            ];
                        }, $this->db->query("SELECT a.*
                        FROM swtar_reread.product_image a 
                        WHERE a.p_id = ?", [$e->product_id])->result())
                    ]
                ];
            }, $result);

            $quantity = array_sum(array_map(fn ($e) => $e['amount'], $result));
            $setting = $this->db->query("SELECT * FROM swtar_reread.setting_delivery ")->result();
            $cost = self::calculateShippingCost($quantity, $setting);


            self::setRes($result, 200, [
                'delivery' => $quantity * $cost,
                'amount' => $quantity,
            ]);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function  calculateShippingCost($quantity, $rules)
    {
        foreach ($rules as $rule) {
            switch ($rule->operater) {
                case '<':
                    if ($quantity < $rule->amount) return $rule->price;
                    break;
                case '>':
                    if ($quantity > $rule->amount) return $rule->price;
                    break;
                case '<=':
                    if ($quantity <= $rule->amount) return $rule->price;
                    break;
                case '>=':
                    if ($quantity >= $rule->amount) return $rule->price;
                    break;
            }
        }
        return 35; // กรณีไม่เข้าเงื่อนไขใดเลย
    }
    public function pushCart_post()
    {
        try {
            $req = (object) $this->input->post();

            if (!$req->uid) self::setRes("INPUT NOT FOUND", 401);
            $person = $this->db->query("SELECT * FROM swtar_reread.personaldocument WHERE pd_id = ?", [$req->uid])->row();
            if (!$person) self::setRes("NOT FOUND", 400);
            $product = $this->db->query("SELECT * FROM swtar_reread.products WHERE id = ? ", [$req->id])->row();
            if (!$product) self::setRes("NOT FOUND", 400);


            $check = $this->db->query("SELECT * FROM swtar_reread.orders WHERE product_id = ? AND pd_id =? AND status='pending'", [$req->id, $req->uid])->row();

            if (!$check) {
                $amount = 1;
                $total = ($product->p_price * $amount);
                $vat =   $total * 0.7;
                $this->db->insert('swtar_reread.orders', [
                    'pd_id' => $req->uid,
                    'product_id' => $req->id,
                    'price' => $product->p_price,
                    'amount' => $amount,
                    'discount' => 0,
                    'total_price' => $total,
                    'vat' =>   $vat,
                    'grand_total' => $total,
                    'status' => 'pending',
                ]);
            } else {
                self::setOrder($check->amount, $check->id, $product->price, $req->uid, $req->id, true);
            }


            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    private function setOrder($_amount, $order_id, $price, $pd_id, $product_id, $increase = true)
    {
        if ($increase) {
            $amount = $_amount + 1;
        } else {
            $amount = $_amount - 1;
        }
        if ($amount <= 0) {
            $this->db->delete('swtar_reread.orders', ['id' => $order_id,]);
        } else {
            $total = ($price * $amount);
            $vat =   $total * 0.7;
            $this->db->update('swtar_reread.orders', [
                'amount' => $amount,
                'discount' => round(0, 2),
                'total_price' => round($total, 2),
                'vat' =>   $vat,
                'grand_total' => round($total, 2),
            ], [
                'id' => $order_id,
                'pd_id' => $pd_id,
                'product_id' => $product_id
            ]);
        }
    }
    public function updateCart_post()
    {
        try {
            $req = (object) $this->input->post();

            if (!$req->uid) self::setRes("INPUT NOT FOUND", 401);
            $person = $this->db->query("SELECT * FROM swtar_reread.personaldocument WHERE pd_id = ?", [$req->uid])->row();
            if (!$person) self::setRes("NOT FOUND", 400);


            $check = $this->db->query("SELECT * FROM swtar_reread.orders WHERE id = ? AND pd_id =? AND status='pending'", [$req->id, $req->uid])->row();
            $increase = true;
            if (!$req->increase) $increase = false;

            $product = $this->db->query("SELECT * FROM swtar_reread.products WHERE id = ? ", [$check->product_id])->row();
            if (!$product) self::setRes("NOT FOUND", 400);
            self::setOrder($check->amount, $req->id, $product->p_price, $req->uid, $product->id, $increase);


            self::setRes("SUCCESS", 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function paymentOrder_post()
    {
        try {
            $req = (object) $this->input->post();

            $this->db->insert('swtar_reread.orders_payment', [
                'pd_id' => $req->uid,
                'expire_date' => date('Y-m-d H:i', strtotime("+1DAY")),
                'price' => $req->price,
                'delivery_amount' => $req->delivery_amount,
                'discount' => $req->discount,
                'total_price' => $req->total
            ]);
            $last_id = $this->db->insert_id();
            $number = date('Ymd') . str_pad($last_id, 4, 0, STR_PAD_LEFT);
            $this->db->update('swtar_reread.orders_payment', ['order_number' =>  $number], ['id' => $last_id]);
            foreach ($req->order_id as $id) {
                $this->db->update('swtar_reread.orders', ['status' => 'paid', 'order_payment_id' => $last_id], ['id' => $id]);
            }


            self::setRes("SUCCESS", 200, ['order_number' =>  $number]);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getHistorys_get()
    {
        try {
            $filter = "status IN ('paid','completed')";
            if ($this->login == 'emp') {
                $filter = "  pd_id = $this->pd_id";
            }

            $result = $this->db->query(
                "SELECT * FROM swtar_reread.orders_payment WHERE   $filter",
            )->result();
            $result = array_map(function ($e) {
                $payment = $this->db->get_where('swtar_reread.payments', ['order_id' => $e->id])->row();
                $payment = [
                    'picture' => '/api' . substr($payment->picture, 1),
                    'type' => $payment->payment_method,
                    'address' => $payment->address,
                ];
                return [
                    '_i' => (int)$e->id,
                    'price' => round($e->price, 2),
                    'discount' => round($e->discount, 2),
                    'total_price' => round($e->total_price, 2),
                    'order_number' => $e->order_number,
                    'date' => date('d-M-Y', strtotime($e->created_at)),
                    'status' => $e->status,
                    'payment' => $this->login == 'emp' ? null : $payment,
                ];
            }, $result);
            self::setRes($result, 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function cancelOrder_post($id)
    {
        try {
            if (!$id) self::setErr('INPUT ERROR', 403);
            $exist = $this->db->query("SELECT * FROM swtar_reread.orders_payment WHERE pd_id = ? AND id = ? AND status ='pending'", [$this->pd_id, $id])->row();
            if (!$exist) self::setErr('NOT FOUND', 404);

            $this->db->update('swtar_reread.orders_payment', ['status' => 'canceled'], ['pd_id' => $this->pd_id, 'id' => $id]);
            $this->db->update('swtar_reread.orders', ['status' => 'canceled'], ['order_payment_id' => $id]);

            self::setRes('SUCCESS', 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function successOrder_post($id)
    {
        try {
            if (!$id) self::setErr('INPUT ERROR', 403);
            $exist = $this->db->query("SELECT * FROM swtar_reread.orders_payment WHERE id = ? AND status ='paid'", [$id])->row();
            if (!$exist) self::setErr('NOT FOUND', 404);

            $this->db->update('swtar_reread.orders_payment', ['status' => 'completed'], ['id' => $id]);
            $this->db->update('swtar_reread.orders', ['status' => 'completed'], ['order_payment_id' => $id]);

            self::setRes('SUCCESS', 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function getOrderPayment_get()
    {
        try {
            $req = (object)$this->input->get();

            $result = $this->db->query(
                "SELECT a.id as _oid ,a.order_number,a.created_at as date_paid, b.* FROM swtar_reread.orders_payment  a 
                LEFT JOIN swtar_reread.orders b ON b.order_payment_id = a.id AND a.pd_id = b.pd_id
                WHERE a.pd_id = ? AND a.order_number = ?",
                [$this->pd_id, $req->order_number]
            )->result();

            $result = array_map(function ($e) {
                $_p = $this->db->get_where('swtar_reread.products', ['id' => $e->product_id])->row();
                return [
                    '_i' => (int)$e->_oid,
                    'product' => [
                        'no' =>   $_p->p_no,
                        'name' =>   $_p->p_name,
                        'detail' =>   $_p->p_detail,
                        'price' => (float)  $e->total_price,
                        'discount' => (float)  $e->discount,
                        'cate_id' => array_map(function ($e) {
                            return [
                                '_i' => (int) $e->id,
                                'name' => $e->cate_name,
                            ];
                        }, $this->db->query("SELECT a.*
                    FROM swtar_reread.category a 
                    LEFT JOIN swtar_reread.products_cate b ON b.cate_id = a.id
                    WHERE b.p_id = ?", [$_p->id])->result()),
                        'picture' => array_map(function ($e) {
                            return [
                                '_i' => (int) $e->id,
                                'path' => '/api' . substr($e->picture, 1),
                            ];
                        }, $this->db->query("SELECT a.*
                    FROM swtar_reread.product_image a 
                    WHERE a.p_id = ?", [$_p->id])->result()),
                        'path_group' => array_map(function ($e) {
                            return '/api' . ($e->picture);
                        }, $this->db->query("SELECT a.*
                    FROM swtar_reread.product_image a 
                    WHERE a.p_id = ?", [$_p->id])->result()),
                    ],
                ];
            }, $result);
            $exist = $this->db->query("SELECT * FROM swtar_reread.orders_payment WHERE order_number = ?", [$req->order_number])->row();

            self::setRes($result, 200, [
                'id' => (int)$exist->id,
                'order_number' => $req->order_number,
                'status' => $exist->status,
                'discount' => round($exist->discount, 2),
                'delivery_amount' => round($exist->delivery_amount, 2),
                'total_price' => round($exist->total_price, 2),
            ]);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
    public function paymentPaid_post()
    {
        try {
            $req = (object)$this->input->post();

            if (!$req->id) self::setErr('INPUT ERROR', 403);
            $exist = $this->db->query("SELECT * FROM swtar_reread.orders_payment WHERE pd_id = ? AND id = ? AND status ='pending'", [$this->pd_id, $req->id])->row();
            if (!$exist) self::setErr('NOT FOUND', 404);

            $this->db->update('swtar_reread.orders_payment', ['status' => 'paid'], ['pd_id' => $this->pd_id, 'id' => $req->id]);
            $this->db->update('swtar_reread.orders', ['status' => 'paid'], ['order_payment_id' => $req->id]);
            $files = $this->Upload->upload_images('payment');
            $data = [
                'pd_id' => $this->pd_id,
                'order_id' => $req->id,
                'payment_method' => $req->payment_method,
                'picture' => $files[0],
                'address' => $req->address ?? null
            ];

            $this->db->insert('swtar_reread.payments', $data);

            self::setRes('SUCCESS', 200);
        } catch (Exception $e) {
            self::sendResponse($e, __METHOD__);
        }
    }
}
