<?php
   header("Access-Control-Allow-Origin: *");
   header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
   header("Access-Control-Allow-Headers: Content-Type, Authorization");
   if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      http_response_code(200);
      exit;
   }

   const DB_SERVER   = "localhost";
   const DB_USERNAME = "owner01";
   const DB_PASSWORD = "123456";
   const DB_NAME     = "tegrand";

   function create_connection()
   {
      $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
      if (! $conn) {
         echo json_encode(["state" => false, "message" => "連線失敗!"]);
         exit;
      }
      return $conn;
   }

   function get_json_input()
   {
      $data = file_get_contents("php://input");
      return json_decode($data, true);
   }

   function respond($state, $message, $data = null)
   {
      echo json_encode(["state" => $state, "message" => $message, "data" => $data]);
   }

   function add_item()
   {
      $p_photo = null;

      if (isset($_FILES['file']) && $_FILES['file']['name'] != "") {
         if ($_FILES['file']['type'] == 'image/jpeg' || $_FILES['file']['type'] == 'image/png') {
            $filename = date("YmdHis") . "_" . $_FILES['file']['name'];
            $location = 'upload/' . $filename;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $location)) {
               $p_photo = $location;
            } else {
               respond(false, "檔案上傳失敗");
               return;
            }
         } else {
            respond(false, "檔案必須為 jpeg 或 png!");
            return;
         }
      }

      $input = json_decode($_POST["json_data"], true);

      if (!$input) {
         respond(false, "JSON 資料解析錯誤");
         return;
      }

      if (isset($input["item_category"], $input["item_price"], $input["item_condition"], $input["item_status"])) {
         $p_category  = $input["item_category"];
         $p_price     = $input["item_price"];
         $p_condition = $input["item_condition"];
         $p_status    = $input["item_status"];

         $p_brand   = $input["item_brand"] ?? null;
         $p_name    = $input["item_name"] ?? null;
         $p_type    = $input["item_type"] ?? null;
         $p_age     = $input["item_age"] ?? null;
         $p_description = $input["item_description"] ?? null;
         $p_remark  = $input["item_remark"] ?? null;
         $p_seller = isset($input["item_seller"]) && $input["item_seller"] !== "" ? $input["item_seller"] : null;
         $p_buyer  = isset($input["item_buyer"]) && $input["item_buyer"] !== "" ? $input["item_buyer"] : null;
         
         if ($p_category !== null && $p_price !== null && $p_condition !== null && $p_status !== null) {
            $conn = create_connection();

            $stmt = $conn->prepare("
               INSERT INTO items (item_photo, item_category, item_brand, item_name, item_type, item_price, item_condition, item_status, item_description, item_remark, item_age, item_seller, item_buyer) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssssssssssss", $p_photo, $p_category, $p_brand, $p_name, $p_type, $p_price, $p_condition, $p_status, $p_description, $p_remark, $p_age, $p_seller, $p_buyer);

            if ($stmt->execute()) {
               respond(true, "新增機台成功", ["image_path" => $p_photo]);
            } else {
               respond(false, "新增機台失敗");
            }

            $stmt->close();
            $conn->close();
         } else {
            respond(false, "欄位不得為空");
         }
      } else {
         respond(false, "欄位錯誤");
      }
   }

   function update_item()
   {
      $input = json_decode($_POST["json_data"], true);

      if (!$input) {
         respond(false, "JSON 資料解析錯誤");
         return;
      }

      if (isset($input["item_id"], $input["item_category"], $input["item_price"], $input["item_condition"], $input["item_status"])) { 
         $p_id  = $input["item_id"];
         $p_category  = $input["item_category"];
         $p_price     = $input["item_price"];
         $p_condition = $input["item_condition"];
         $p_status    = $input["item_status"];

         $p_brand   = $input["item_brand"] ?? null;
         $p_name    = $input["item_name"] ?? null;
         $p_type    = $input["item_type"] ?? null;
         $p_age     = $input["item_age"] ?? null;
         $p_description = $input["item_description"] ?? null;
         $p_remark  = $input["item_remark"] ?? null;
         $p_seller = isset($input["item_seller"]) && $input["item_seller"] !== "" ? $input["item_seller"] : null;
         $p_buyer  = isset($input["item_buyer"]) && $input["item_buyer"] !== "" ? $input["item_buyer"] : null;

         if ($p_id && $p_category && $p_price && $p_condition && $p_status) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ?");
            $stmt->bind_param("i", $p_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $originalData = $result->fetch_assoc();
            $stmt->close();

            if (!$originalData) {
               respond(false, "找不到該機台資訊");
               return;
            }

            if(isset($_FILES['file']) && $_FILES['file']['name'] != ""){
               if($_FILES['file']['type'] == 'image/jpeg' || $_FILES['file']['type'] == 'image/png') {
                  $filename = date("YmdHis") . "_" . $_FILES['file']['name'];
                  $location = 'upload/' . $filename;
         
                  if (move_uploaded_file($_FILES['file']['tmp_name'], $location)) {
                     $p_photo = $location;
                  } else {
                     respond(false, "檔案上傳失敗");
                     return;
                  }
               }else{
                  respond(false, "上傳圖片格式錯誤");
                  return;
               }
            }else{
               $p_photo = $input['item_photo'] ?? $originalData['item_photo'];
            }

            if ($p_photo === $originalData['item_photo'] &&
               $p_category === $originalData['item_category'] &&
               $p_brand === $originalData['item_brand'] &&
               $p_name === $originalData['item_name'] &&
               $p_type === $originalData['item_type'] &&
               $p_age === $originalData['item_age'] &&
               $p_price === $originalData['item_price'] &&
               $p_condition === $originalData['item_condition'] &&
               $p_status === $originalData['item_status'] &&
               $p_description === $originalData['item_description'] &&
               $p_remark === $originalData['item_remark'] &&
               $p_seller === $originalData['item_seller'] &&
               $p_buyer === $originalData['item_buyer']
            ) {
               respond(false, "沒有任何變更");
               return;
            }

            $stmt = $conn->prepare("UPDATE items SET item_photo = ?, item_category = ?, item_brand = ?, item_name = ?, item_type = ?, item_age = ?, item_price = ?, item_condition = ?, item_status =?, item_description = ?, item_remark = ?, item_seller = ?, item_buyer = ? WHERE item_id = ?");
            $stmt->bind_param("sssssssssssssi", $p_photo, $p_category, $p_brand, $p_name, $p_type, $p_age, $p_price, $p_condition, $p_status, $p_description, $p_remark, $p_seller, $p_buyer, $p_id); //一定要傳遞變數

            if ($stmt->execute()) {
               if ($stmt->affected_rows === 1) {
                  respond(true, "機台資料庫更新成功");
               } else {
                  respond(false, "機台資料庫更新失敗, 並無更新行為!");
               }
            } else {
               respond(false, "機台資料庫更新失敗");
            }
            $stmt->close();
            $conn->close();
         } else {
            respond(false, "種類、價格、上架狀態、機台狀態為必填項目");
         }
      } else {
         respond(false, "欄位錯誤");
      }
   }

   function delete_selected_item()
   {
      $input = get_json_input();
      if (isset($input["item_ids"]) && is_array($input["item_ids"])) {
         $ids = $input["item_ids"]; 

         if (count($ids) > 0) {
            $conn = create_connection();

            $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("SELECT item_photo FROM items WHERE item_id IN ($ids_placeholder)");

            $types = str_repeat('i', count($ids)); 
            $stmt->bind_param($types, ...$ids);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $photosToDelete = [];
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['item_photo']) && file_exists($row['item_photo'])) {
                    $photosToDelete[] = $row['item_photo'];
                }
            }
            $stmt->close();

            foreach ($photosToDelete as $photo) {
                unlink($photo); 
            }

            $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("DELETE FROM items WHERE item_id IN ($ids_placeholder)");

            $types = str_repeat('i', count($ids)); 
            $stmt->bind_param($types, ...$ids); 

            if ($stmt->execute()) {
               if ($stmt->affected_rows > 0) {
                  respond(true, "選中的機台資訊已成功刪除");
               } else {
                  respond(false, "沒有任何機台資訊被刪除");
               }
            } else {
               respond(false, "刪除操作失敗");
            }
            $stmt->close();
            $conn->close();
         } else {
            respond(false, "沒有選擇機台資訊進行刪除");
         }
      } else {
         respond(false, "欄位錯誤");
      }
   }

   function get_item_data()
   {
      $conn = create_connection();

      $stmt = $conn->prepare("SELECT item_category, COUNT(*) as count FROM items WHERE item_category BETWEEN 1 AND 10 GROUP BY item_category");
      $stmt->execute();
      $result = $stmt->get_result();

      $item_counts = array_fill(1, 10, 0);
      $item_all_count = 0;
      while ($row = $result->fetch_assoc()) {
         $item_counts[$row['item_category']] = $row['count'];
         $item_all_count += $row['count'];
      }

      $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
      $limit = 10; 
      $offset = ($page - 1) * $limit;

      $total_item_pages = [];
      for ($i = 1; $i <= 10; $i++) {
         $total_item_pages[$i] = ceil($item_counts[$i] / $limit);
      }
      $total_item_all_pages = ceil($item_all_count / $limit);

      $stmt = $conn->prepare("SELECT * FROM items WHERE item_category IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10) ORDER BY item_id DESC LIMIT ? OFFSET ?");
      $stmt->bind_param("ii", $limit, $offset);
      $stmt->execute();
      $result = $stmt->get_result();

      $items_by_category = array_fill(1, 10, []);
      $items_data_page = []; 

      while ($item = $result->fetch_assoc()) {
         $item = array_map(fn($value) => $value ?? '', $item);
         
         $category = $item['item_category'];
         if($category >= 1 && $category <= 10) {
            $items_by_category[$category][] = $item;
         }
 
         $items_data_page[] = $item;
      }

      $stmt = $conn->prepare("SELECT * FROM items ORDER BY item_id DESC");
      $stmt->execute();
      $result = $stmt->get_result();

      $items_category_allpage = array_fill(1, 10, []);
      $items_data_allpage = [];

      while ($item = $result->fetch_assoc()) {
         $item = array_map(fn($value) => $value ?? '', $item);
         
         $category = $item['item_category'];
         if ($category >= 1 && $category <= 10) {
            $items_category_allpage[$category][] = $item;
         }
 
         $items_data_allpage[] = $item;
      }

      respond(true, "取得機台相關資料成功", [
         'item_counts' => $item_counts,
         'item_all_count' => $item_all_count,
         'total_item_pages' => $total_item_pages,
         'total_item_all_pages' => $total_item_all_pages,
         'current_page' => $page,
         'items_category_page' => $items_by_category,
         'items_data_page' => $items_data_page,
         'items_category_allpage' => $items_category_allpage,
         'items_data_allpage' => $items_data_allpage
      ]);

      $stmt->close();
      $conn->close();
   }

   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_GET['action'] ?? '';
      switch ($action) {
         case 'add':
            add_item();
            break;
         case 'update':
            update_item();
            break;
         default:
            respond(false, "無效的操作");
      }
   } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $action = $_GET['action'] ?? '';
      switch ($action) {
         case 'getalldata':
            get_item_data();
            break;
         default:
            respond(false, "無效的操作");
      }
   } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
      $action = $_GET['action'] ?? '';
      switch ($action) {
         case 'delete_selected':
            delete_selected_item();
            break;
         default:
            respond(false, "無效的操作");
      }
   } else {
      respond(false, "無效的請求方法");
   }
?>