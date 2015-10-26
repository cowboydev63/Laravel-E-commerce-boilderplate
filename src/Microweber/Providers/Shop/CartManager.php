<?php


namespace Microweber\Providers\Shop;


class CartManager {

    /** @var \Microweber\Application */
    public $app;


    public $table = 'cart';

    function __construct($app = null) {
        if (is_object($app)){
            $this->app = $app;
        } else {
            $this->app = mw();
        }
    }

    /**
     * @param bool $return_amount
     *
     * @return array|false|float|int|mixed
     */
    public function sum($return_amount = true) {

        $sid = $this->app->user_manager->session_id();
        $different_items = 0;
        $amount = floatval(0.00);
        $cart = $this->table;
        $cart_table_real = $this->app->database_manager->real_table_name($cart);
        $sumq = " SELECT  price, qty FROM $cart_table_real WHERE order_completed=0  AND session_id='{$sid}'  ";
        $sumq = $this->app->database_manager->query($sumq);
        if (is_array($sumq)){
            foreach ($sumq as $value) {
                $different_items = $different_items + $value['qty'];
                $amount = $amount + (intval($value['qty']) * floatval($value['price']));
            }
        }
        $modify_amount = $this->app->event_manager->trigger('mw.cart.sum', $amount);
        if ($modify_amount!==null and $modify_amount!==false){
            if (is_array($modify_amount)){
                $pop = array_pop($modify_amount);
                if ($pop!=false){
                    $amount = $pop;

                }
            } else {
                $amount = $modify_amount;
            }
        }
        if ($return_amount==false){
            return $different_items;
        }

        return $amount;
    }


    public function total() {
        $sum = $this->sum();

        $shipping = floatval($this->app->user_manager->session_get('shipping_cost'));

        $total = $sum + $shipping;

        return $total;
    }


    public function get($params = false) {


        $time = time();
        $clear_carts_cache = $this->app->cache_manager->get('clear_cache', 'cart/global');

        if ($clear_carts_cache==false or ($clear_carts_cache < ($time - 600))){
            // clears cache for old carts
            $this->app->cache_manager->delete('cart/global');
            $this->app->cache_manager->save($time, 'clear_cache', 'cart/global');
        }


        $params2 = array();

        if (is_string($params)){
            $params = parse_str($params, $params2);
            $params = $params2;
        }
        $table = $this->table;
        $params['table'] = $table;
        $skip_sid = false;
        if (!defined('MW_API_CALL')){
            if (isset($params['order_id'])){
                $skip_sid = 1;
            }
        }
        if ($skip_sid==false){
            if (!defined('MW_ORDERS_SKIP_SID')){
                if ($this->app->user_manager->is_admin()==false){
                    $params['session_id'] = mw()->user_manager->session_id();
                } else {
                    if (isset($params['session_id']) and $this->app->user_manager->is_admin()==true){

                    } else {
                        $params['session_id'] = mw()->user_manager->session_id();
                    }
                }
                if (isset($params['no_session_id']) and $this->app->user_manager->is_admin()==true){
                    unset($params['session_id']);
                }
            }
        }
        if (!isset($params['rel']) and isset($params['for'])){
            $params['rel_type'] = $params['for'];
        } else if (isset($params['rel']) and !isset($params['rel_type'])){
            $params['rel_type'] = $params['rel'];
        }
        if (!isset($params['rel_id']) and isset($params['for_id'])){
            $params['rel_id'] = $params['for_id'];
        }

        $params['limit'] = 10000;
        if (!isset($params['order_completed'])){
            if (!isset($params['order_id'])){
                $params['order_completed'] = 0;
            }
        } elseif (isset($params['order_completed']) and $params['order_completed']==='any') {

            unset($params['order_completed']);
        }
        // $params['no_cache'] = 1;

        $get = $this->app->database_manager->get($params);
        if (isset($params['count']) and $params['count']!=false){
            return $get;
        }
        $return = array();
        if (is_array($get)){
            foreach ($get as $k => $item) {
                if (isset($item['rel_id']) and isset($item['rel_type']) and $item['rel_type']=='content'){
                    $item['content_data'] = $this->app->content_manager->data($item['rel_id']);
                }
                if (isset($item['custom_fields_data']) and $item['custom_fields_data']!=''){
                    $item = $this->app->format->render_item_custom_fields_data($item);
                }
                if (isset($item['title'])){
                    $item['title'] = html_entity_decode($item['title']);
                    $item['title'] = strip_tags($item['title']);
                    $item['title'] = $this->app->format->clean_html($item['title']);
                    $item['title'] = htmlspecialchars_decode($item['title']);

                }
                $return[ $k ] = $item;
            }
        } else {
            $return = $get;
        }

        return $return;
    }


    public function remove_item($data) {

        if (!is_array($data)){
            $id = intval($data);
            $data = array('id' => $id);
        }


        if (!isset($data['id']) or $data['id']==0){
            return false;
        }

        $cart = array();
        $cart['id'] = intval($data['id']);

        if ($this->app->user_manager->is_admin()==false){
            $cart['session_id'] = mw()->user_manager->session_id();
        }
        $cart['order_completed'] = 0;

        $cart['one'] = 1;
        $cart['limit'] = 1;
        $check_cart = $this->get($cart);

        if ($check_cart!=false and is_array($check_cart)){
            $table = $this->table;
            $this->app->database_manager->delete_by_id($table, $id = $cart['id'], $field_name = 'id');
        } else {

        }
    }

    public function update_item_qty($data) {

        if (!isset($data['id'])){
            $this->app->error('Invalid data');
        }
        if (!isset($data['qty'])){
            $this->app->error('Invalid data');
        }
        $cart = array();
        $cart['id'] = intval($data['id']);
        $cart['session_id'] = mw()->user_manager->session_id();

        $cart['order_completed'] = 0;
        $cart['one'] = 1;
        $cart['limit'] = 1;
        $check_cart = $this->get($cart);
        if (isset($check_cart['rel_type']) and isset($check_cart['rel_id']) and $check_cart['rel_type']=='content'){
            $data_fields = $this->app->content_manager->data($check_cart['rel_id'], 1);
            if (isset($check_cart['qty']) and isset($data_fields['qty']) and $data_fields['qty']!='nolimit'){
                $old_qty = intval($data_fields['qty']);
                if (intval($data['qty']) > $old_qty){
                    return false;
                }
            }
        }
        if ($check_cart!=false and is_array($check_cart)){
            $cart['qty'] = intval($data['qty']);
            if ($cart['qty'] < 0){
                $cart['qty'] = 0;
            }
            $table = $this->table;
            $cart_data_to_save = array();
            $cart_data_to_save['qty'] = $cart['qty'];
            $cart_data_to_save['id'] = $cart['id'];
            $cart_saved_id = $this->app->database_manager->save($table, $cart_data_to_save);

            return ($cart_saved_id);
        }
    }


    function empty_cart() {
        $sid = mw()->user_manager->session_id();
        $cart_table = $this->table;

        \Cart::where('order_completed', 0)->where('session_id', $sid)->delete();
        $this->no_cache = true;
        $this->app->cache_manager->delete('cart');
        $this->app->cache_manager->delete('cart_orders/global');

    }

    public function update_cart($data) {
        if (isset($data['content_id'])){
            $data['for'] = 'content';
            $for_id = $data['for_id'] = $data['content_id'];
        }
        $override = $this->app->event_manager->trigger('mw.shop.update_cart', $data);
        if (is_array($override)){
            foreach ($override as $resp) {
                if (is_array($resp) and !empty($resp)){
                    $data = array_merge($data, $resp);
                }
            }
        }
        if (!isset($data['for'])){
            $data['for'] = 'content';
        }
        $update_qty = 0;
        $update_qty_new = 0;

        if (isset($data['qty'])){
            $update_qty_new = $update_qty = intval($data['qty']);
            unset($data['qty']);
        }
        if (!isset($data['for']) or !isset($data['for_id'])){
            if (!isset($data['id'])){

            } else {
                $cart = array();
                $cart['id'] = intval($data['id']);
                $cart['limit'] = 1;
                $data_existing = $this->get($cart);
                if (is_array($data_existing) and is_array($data_existing[0])){
                    $data = array_merge($data, $data_existing[0]);
                }
            }
        }


        if (!isset($data['for']) and isset($data['rel_type'])){
            $data['for'] = $data['rel_type'];
        }
        if (!isset($data['for_id']) and isset($data['rel_id'])){
            $data['for_id'] = $data['rel_id'];
        }
        if (!isset($data['for']) and !isset($data['for_id'])){
            $this->app->error('Invalid for and for_id params');
        }

        $data['for'] = $this->app->database_manager->assoc_table_name($data['for']);
        $for = $data['for'];
        $for_id = intval($data['for_id']);
        if ($for_id==0){
            $this->app->error('Invalid data');
        }
        $cont_data = false;

        if ($update_qty > 0){
            $data['qty'] = $update_qty;
        }

        if ($data['for']=='content'){
            $cont = $this->app->content_manager->get_by_id($for_id);
            $cont_data = $this->app->content_manager->data($for_id);
            if ($cont==false){
                $this->app->error('Invalid product?');
            } else {
                if (is_array($cont) and isset($cont['title'])){
                    $data['title'] = $cont['title'];
                }
            }
        }

        if (isset($data['title']) and is_string($data['title'])){
            $data['title'] = (strip_tags($data['title']));
        }

        $found_price = false;
        $add = array();

        if (isset($data['custom_fields_data']) and is_array($data['custom_fields_data'])){
            $add = $data['custom_fields_data'];
        }

        $prices = array();

        $skip_keys = array();

        $content_custom_fields = array();
        $content_custom_fields = $this->app->fields_manager->get($for, $for_id, 1);


        if ($content_custom_fields==false){
            $content_custom_fields = $data;
            if (isset($data['price'])){
                $found_price = $data['price'];
            }
        } elseif (is_array($content_custom_fields)) {
            foreach ($content_custom_fields as $cf) {
                if (isset($cf['type']) and $cf['type']=='price'){
                    $prices[ $cf['name'] ] = $cf['value'];
                }
            }
        }


        foreach ($data as $k => $item) {
            if ($k!='for' and $k!='for_id' and $k!='title'){
                $found = false;
                foreach ($content_custom_fields as $cf) {
                    if (isset($cf['type']) and isset($cf['name']) and $cf['type']!='price'){
                        $key1 = str_replace('_', ' ', $cf['name']);
                        $key2 = str_replace('_', ' ', $k);
                        if (isset($cf['name']) and ($cf['name']==$k or $key1==$key2)){
                            $k = str_replace('_', ' ', $k);
                            $found = true;
                            if (is_array($cf['values'])){
                                if (in_array($item, $cf['values'])){
                                    $found = true;
                                }
                            }
                            if ($found==false and $cf['value']!=$item){
                                unset($item);
                            }
                        }
                    } elseif (isset($cf['type']) and $cf['type']=='price') {
                        if ($cf['value']!=''){
                            $prices[ $cf['name'] ] = $cf['value'];
                        }
                    } elseif (isset($cf['type']) and $cf['type']=='price') {
                        if ($cf['value']!=''){
                            $prices[ $cf['name'] ] = $cf['value'];

                        }
                    }
                }
                if ($found==false){
                    $skip_keys[] = $k;
                }

                if (is_array($prices)){
                    foreach ($prices as $price_key => $price) {
                        if (isset($data['price'])){
                            if ($price==$data['price']){
                                $found = true;
                                $found_price = $price;
                            }
                        } else if ($price==$item){
                            $found = true;
                            if ($found_price==false){
                                $found_price = $item;
                            }
                        }
                    }
                    if ($found_price==false){
                        $found_price = array_pop($prices);
                    } else {
                        if (count($prices) > 1){
                            foreach ($prices as $pk => $pv) {
                                if ($pv==$found_price){
                                    $add[ $pk ] = $this->app->shop_manager->currency_format($pv);
                                }
                            }
                        }
                    }
                }
                if (isset($item)){
                    if ($found==true){
                        if ($k!='price' and !in_array($k, $skip_keys)){
                            $add[ $k ] = $this->app->format->clean_html($item);
                        }
                    }
                }

            }

        }

        if ($found_price==false and is_array($prices)){
            $found_price = array_pop($prices);
        }
        if ($found_price==false){
            $found_price = 0;
        }


        if (is_array($prices)){
            ksort($add);
            asort($add);
            $table = $this->table;
            $cart = array();
            $cart['rel_type'] = ($data['for']);
            $cart['rel_id'] = intval($data['for_id']);
            $cart['title'] = ($data['title']);
            $cart['price'] = floatval($found_price);

            $cart_return = $cart;
            $cart_return['custom_fields_data'] = $add;
            $cart['custom_fields_data'] = $this->app->format->array_to_base64($add);
            $cart['order_completed'] = 0;
            $cart['session_id'] = mw()->user_manager->session_id();

            $cart['limit'] = 1;
            $check_cart = $this->get($cart);
            if ($check_cart!=false and is_array($check_cart) and isset($check_cart[0])){

                $cart['id'] = $check_cart[0]['id'];
                if ($update_qty > 0){
                    $cart['qty'] = $check_cart[0]['qty'] + $update_qty;
                } elseif ($update_qty_new > 0) {
                    $cart['qty'] = $update_qty_new;
                } else {
                    $cart['qty'] = $check_cart[0]['qty'] + 1;
                }

            } else {

                if ($update_qty > 0){
                    $cart['qty'] = $update_qty;
                } else {
                    $cart['qty'] = 1;
                }
            }

            if (isset($cont_data['qty']) and trim($cont_data['qty'])!='nolimit'){
                if (intval($cont_data['qty']) < intval($cart['qty'])){
                    $cart['qty'] = $cont_data['qty'];
                }
            }

            if (isset($data['other_info']) and is_string($data['other_info'])){
                $cart['other_info'] = strip_tags($data['other_info']);
            }

            if (isset($data['item_image']) and is_string($data['item_image'])){
                $cart['item_image'] = mw()->format->clean_xss(strip_tags($data['item_image']));
            }

            $cart_saved_id = $this->app->database_manager->save($table, $cart);
            $this->app->cache_manager->delete('cart');
            $this->app->cache_manager->delete('cart_orders/global');

            if (isset($cart['rel_type']) and isset($cart['rel_id']) and $cart['rel_type']=='content'){
                $cart_return['image'] = $this->app->media_manager->get_picture($cart['rel_id']);
                $cart_return['product_link'] = $this->app->content_manager->link($cart['rel_id']);

            }

            return array('success' => 'Item added to cart', 'product' => $cart_return);

        } else {
            return array('error' => 'Invalid cart items');
        }

    }


    public function recover_cart($sid = false, $ord_id = false) {
        if ($sid==false){
            return;
        }
        $cur_sid = mw()->user_manager->session_id();
        if ($cur_sid==false){
            return;
        } else {
            if ($cur_sid!=false){
                $c_id = $sid;
                $table = $this->table;
                $params = array();
                $params['order_completed'] = 0;
                $params['session_id'] = $c_id;
                $params['table'] = $table;
                if ($ord_id!=false){
                    unset($params['order_completed']);
                    $params['order_id'] = intval($ord_id);
                }

                $will_add = true;
                $res = $this->app->database_manager->get($params);


                if (!empty($res)){
                    foreach ($res as $item) {
                        if (isset($item['id'])){
                            $data = $item;
                            unset($data['id']);
                            if (isset($item['order_id'])){
                                unset($data['order_id']);
                            }
                            if (isset($item['created_by'])){
                                unset($data['created_by']);
                            }
                            if (isset($item['updated_at'])){
                                unset($data['updated_at']);
                            }
                            if (isset($item['rel_type']) and isset($item['rel_id'])){
                                $is_ex_params = array();
                                $is_ex_params['order_completed'] = 0;
                                $is_ex_params['session_id'] = $cur_sid;
                                $is_ex_params['table'] = $table;
                                $is_ex_params['rel_type'] = $item['rel_type'];
                                $is_ex_params['rel_id'] = $item['rel_id'];
                                $is_ex_params['count'] = 1;

                                $is_ex = $this->app->database_manager->get($is_ex_params);

                                if ($is_ex!=false){
                                    $will_add = false;
                                }
                            }
                            $data['order_completed'] = 0;
                            $data['session_id'] = $cur_sid;
                            if ($will_add==true){
                                $s = $this->app->database_manager->save($table, $data);
                            }
                        }
                    }
                }
                if ($will_add==true){
                    $this->app->cache_manager->delete('cart');

                    $this->app->cache_manager->delete('cart_orders/global');
                }
            }
        }

    }
}