<?php
   // 跨域請求
   header("Access-Control-Allow-Origin: *");
   // 允許的請求方法
   header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
   // 允許的請求頭部
   header("Access-Control-Allow-Headers: Content-Type, Authorization");
   // 如果是 OPTIONS 請求，直接回應
   if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      http_response_code(200);
      exit;
   }

   const DB_SERVER   = "localhost";
   const DB_USERNAME = "owner01";
   const DB_PASSWORD = "123456";
   const DB_NAME     = "tegrand";

   //建立連線
   function create_connection()
   {
      $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
      if (! $conn) {
         echo json_encode(["state" => false, "message" => "連線失敗!"]);
         exit;
      }
      return $conn;
   }

   //取得JSON的資料
   function get_json_input()
   {
      $data = file_get_contents("php://input");
      return json_decode($data, true);
   }

   //回復JSON訊息
   //state: 狀態(成功或失敗) message: 訊息內容 data: 回傳資料(可有可無)
   function respond($state, $message, $data = null)
   {
      echo json_encode(["state" => $state, "message" => $message, "data" => $data]);
   }

   //新增機台
   function add_item()
   {
      $p_photo = null;

      // 檢查是否有檔案上傳
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

      // 取得 JSON 資料（從 FormData 的 "json_data" 取得）
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

      if (isset($input["item_id"], $input["item_category"], $input["item_price"], $input["item_condition"], $input["item_status"])) {  //只檢查id、種類、價格、上架狀態、機台狀態必填(5項)
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

            // 先查詢目前的資料
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

            // 檢查是否有檔案上傳
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
               // 沒有新上傳的圖片，保持原始圖片路徑
               $p_photo = $input['item_photo'] ?? $originalData['item_photo'];
            }

            // 比對資料是否有變更(買家、賣家為會員id，買家與購物相關，賣家與機台收購相關，之後再修改)
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

            //執行更新
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

   // 機台資訊刪除
   // {"id" : "xxxxxx"}
   // {"state" : true, "message" : "機台資料刪除成功"}
   // {"state" : false, "message" : "機台資料刪除失敗與相關錯誤訊息"}
   // {"state" : false, "message" : "欄位錯誤"}
   // {"state" : false, "message" : "欄位不得為空白"}
   // function delete_item()
   // {
   //    $input = get_json_input();
   //    if (isset($input["item_id"])) {
   //       $p_id = trim($input["item_id"]);
   //       if ($p_id) {
   //          $conn = create_connection();

   //          $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
   //          $stmt->bind_param("i", $p_id); //一定要傳遞變數

   //          if ($stmt->execute()) {
   //             if ($stmt->affected_rows === 1) {
   //                respond(true, "機台資訊刪除成功");
   //             } else {
   //                respond(false, "機台資訊刪除失敗, 並無刪除行為!");
   //             }
   //          } else {
   //             respond(false, "機台資訊刪除失敗");
   //          }
   //          $stmt->close();
   //          $conn->close();
   //       } else {
   //          respond(false, "欄位不得為空");
   //       }
   //    } else {
   //       respond(false, "欄位錯誤");
   //    }
   // }

   //批量機台資料刪除
   function delete_selected_item()
   {
      $input = get_json_input();
      if (isset($input["item_ids"]) && is_array($input["item_ids"])) {
         $ids = $input["item_ids"];  // 這是包含所有 ID 的陣列

         if (count($ids) > 0) {
            $conn = create_connection();

            // 1. 先查詢所有對應的圖片路徑
            $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("SELECT item_photo FROM items WHERE item_id IN ($ids_placeholder)");

            $types = str_repeat('i', count($ids)); // 所有參數都是整數
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

            // 2. 刪除對應的圖片檔案
            foreach ($photosToDelete as $photo) {
                unlink($photo); // 移除檔案
            }

            // 3. 執行批量刪除 SQL
            $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("DELETE FROM items WHERE item_id IN ($ids_placeholder)");

            // 綁定參數
            $types = str_repeat('i', count($ids));  // 所有參數都是整數型別
            $stmt->bind_param($types, ...$ids);  // 傳遞參數

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

   //取得機台的資料、數量、分頁資料
   function get_item_data()
   {
      $conn = create_connection();

      // 取得各類機台數量 (item_category=1~10, all)
      $stmt = $conn->prepare("SELECT item_category, COUNT(*) as count FROM items WHERE item_category BETWEEN 1 AND 10 GROUP BY item_category");
      $stmt->execute();
      $result = $stmt->get_result();

      // 設定變數
      $item_counts = array_fill(1, 10, 0); // 預設 0
      $item_all_count = 0;
      while ($row = $result->fetch_assoc()) {
         $item_counts[$row['item_category']] = $row['count'];
         $item_all_count += $row['count'];
      }

      // 設定分頁參數
      $page = isset($_GET['page']) ? intval($_GET['page']) : 1; // 預設第一頁
      $limit = 10; // 每頁顯示 10 筆
      $offset = ($page - 1) * $limit;

      // 計算總頁數(item_category=1~10、all)
      $total_item_pages = [];
      for ($i = 1; $i <= 10; $i++) {
         $total_item_pages[$i] = ceil($item_counts[$i] / $limit);
      }
      $total_item_all_pages = ceil($item_all_count / $limit);

      // 有分頁
      $stmt = $conn->prepare("SELECT * FROM items WHERE item_category IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10) ORDER BY item_id DESC LIMIT ? OFFSET ?");
      $stmt->bind_param("ii", $limit, $offset);
      $stmt->execute();
      $result = $stmt->get_result();

      $items_by_category = array_fill(1, 10, []); // 分類後的機台資料(有分頁)
      $items_data_page = []; // 所有機台（有分頁）

      while ($item = $result->fetch_assoc()) {
         // 處理 NULL 值
         $item = array_map(fn($value) => $value ?? '', $item);
         
         $category = $item['item_category'];
         if($category >= 1 && $category <= 10) {
            $items_by_category[$category][] = $item;
         }
 
         $items_data_page[] = $item; // 存放所有資料
      }

      // 不分頁
      $stmt = $conn->prepare("SELECT * FROM items ORDER BY item_id DESC");
      $stmt->execute();
      $result = $stmt->get_result();

      $items_category_allpage = array_fill(1, 10, []); // 分類後的機台資料
      $items_data_allpage = []; // 所有機台資料

      while ($item = $result->fetch_assoc()) {
         // 處理 NULL 值
         $item = array_map(fn($value) => $value ?? '', $item);
         
         $category = $item['item_category'];
         if ($category >= 1 && $category <= 10) {
            $items_category_allpage[$category][] = $item;
         }
 
         $items_data_allpage[] = $item; // 存放所有資料
      }

      // 將結果回傳給前端
      respond(true, "取得機台相關資料成功", [
         'item_counts' => $item_counts, // 各類機台數量ex:$item_counts[1]
         'item_all_count' => $item_all_count, // 總機台數量
         'total_item_pages' => $total_item_pages, // 各類機台總頁數ex:$total_item_pages[1]
         'total_item_all_pages' => $total_item_all_pages, // 總頁數
         'current_page' => $page, // 當前頁數

         'items_category_page' => $items_by_category, // 分類後的機台資料(有分頁)
         'items_data_page' => $items_data_page, // 所有機台資料（有分頁）
         'items_category_allpage' => $items_category_allpage, // 分類後的機台資料(不分頁)
         'items_data_allpage' => $items_data_allpage // 所有機台資料(不分頁)
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
         // case 'delete':
         //    delete_item();
         //    break;
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