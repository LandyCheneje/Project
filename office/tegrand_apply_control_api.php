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

   //新增應徵簡歷
   function add_apply()
   {
      $input = get_json_input();
      if (isset($input["apply_name"], $input["apply_phone"], $input["apply_email"], $input["apply_education"], $input["apply_experience"], $input["apply_position"])) {
         // 必填項目
         $p_name = $input["apply_name"];
         $p_phone = $input["apply_phone"];
         $p_email = $input["apply_email"];
         $p_education = $input["apply_education"];
         $p_experience = $input["apply_experience"];
         $p_position = $input["apply_position"];
         
         // 選填項目
         $p_skill   = isset($input["apply_skill"]) && $input["apply_skill"] !== "" ? $input["apply_skill"] : null;

         // 預設應徵意願回復情形為1.尚未聯繫 
         $p_reply = 1;

         if ($p_name && $p_phone && $p_email && $p_education && $p_experience && $p_position) {
            $conn = create_connection();

            $stmt = $conn->prepare("INSERT INTO apply( apply_name, apply_phone, apply_email, apply_education, apply_experience, apply_skill, apply_position, apply_reply) VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $p_name, $p_phone, $p_email, $p_education, $p_experience, $p_skill, $p_position, $p_reply);

            if ($stmt->execute()) {
               respond(true, "求職簡歷已送出<br><span class='text-primary small'>得昌行將儘速與您聯繫!</span>");
            } else {
               respond(false, "求職簡歷送出失敗");
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

   function update_apply()
   {
      $input = get_json_input();
      if (isset($input["apply_name"], $input["apply_phone"], $input["apply_email"], $input["apply_education"], $input["apply_experience"], $input["apply_position"])) {  //檢查必填項目
         $p_id         = trim($input["apply_id"]);
         $p_name       = trim($input["apply_name"]);
         $p_email      = trim($input["apply_email"]);
         $p_education  = trim($input["apply_education"]);
         $p_experience = trim($input["apply_experience"]);
         $p_position   = trim($input["apply_position"]);
         $p_reply      = trim($input["apply_reply"]);

         $p_skill      = isset($input["apply_skill"]) ? trim($input["apply_skill"]) : null;
         $p_interview      = isset($input["apply_interview"]) ? trim($input["apply_interview"]) : null;

         if ($p_id && $p_reply) {
            $conn = create_connection();

            // 先查詢目前的資料
            $stmt = $conn->prepare("SELECT * FROM apply WHERE apply_id = ?");
            $stmt->bind_param("i", $p_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $originalData = $result->fetch_assoc();
            $stmt->close();

            if (!$originalData) {
               respond(false, "找不到該求職者");
               return;
            }

            // 比對資料是否有變更
            if ($p_reply === $originalData['apply_reply'] && $p_interview === $originalData['apply_interview']){
               respond(false, "沒有任何變更");
               return;
            }

            //執行更新
            $stmt = $conn->prepare("UPDATE apply SET apply_reply = ?, apply_interview = ? WHERE apply_id = ?");
            $stmt->bind_param("ssi", $p_reply, $p_interview, $p_id); //一定要傳遞變數

            if ($stmt->execute()) {
               if ($stmt->affected_rows === 1) {
                  respond(true, "變更成功");
               } else {
                  respond(false, "變更失敗, 無變更行為!");
               }
            } else {
               respond(false, "變更失敗");
            }
            $stmt->close();
            $conn->close();
         } else {
            respond(false, "應徵意願回復情形為必填項目");
         }
      } else {
         respond(false, "欄位錯誤");
      }
   }

   //批量簡歷刪除
   function delete_apply()
   {
      $input = get_json_input();
      if (isset($input["ids"]) && is_array($input["ids"])) {
         $ids = $input["ids"];  // 這是包含所有 ID 的陣列

         if (count($ids) > 0) {
            $conn = create_connection();

            // 用於批量刪除的 SQL 語句
            $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("DELETE FROM apply WHERE apply_id IN ($ids_placeholder)");

            // 綁定參數
            $types = str_repeat('i', count($ids));  // 所有參數都是整數型別
            $stmt->bind_param($types, ...$ids);  // 傳遞參數

            if ($stmt->execute()) {
               if ($stmt->affected_rows > 0) {
                  respond(true, "選中的簡歷已成功刪除");
               } else {
                  respond(false, "沒有任何簡歷被刪除");
               }
            } else {
               respond(false, "刪除操作失敗");
            }
            $stmt->close();
            $conn->close();
         } else {
            respond(false, "沒有選擇簡歷進行刪除");
         }
      } else {
         respond(false, "欄位錯誤");
      }
   }

   //取得應徵意願資料、總數量、總數分頁資料
   //結案   apply_reply = 3, 5, 6, 7 資料、數量、分頁資料
   //未結案 apply_reply = 1, 2, 4    資料、數量、分頁資料
   function get_apply_data()
   {
      $conn = create_connection();

      //取得取得應徵意願回復情形各態樣(1~7)數量
      $stmt = $conn->prepare("SELECT 
         (SELECT COUNT(*) FROM apply WHERE apply_reply = 1) AS reply_1_count,
         (SELECT COUNT(*) FROM apply WHERE apply_reply = 2) AS reply_2_count,
         (SELECT COUNT(*) FROM apply WHERE apply_reply = 3) AS reply_3_count,
         (SELECT COUNT(*) FROM apply WHERE apply_reply = 4) AS reply_4_count,
         (SELECT COUNT(*) FROM apply WHERE apply_reply = 5) AS reply_5_count,
         (SELECT COUNT(*) FROM apply WHERE apply_reply = 6) AS reply_6_count,
         (SELECT COUNT(*) FROM apply WHERE apply_reply = 7) AS reply_7_count ");
      $stmt->execute();
      $result = $stmt->get_result();
      $counts = $result->fetch_assoc();
      $reply_1_count = $counts['reply_1_count'];
      $reply_2_count = $counts['reply_2_count'];
      $reply_3_count = $counts['reply_3_count'];
      $reply_4_count = $counts['reply_4_count'];
      $reply_5_count = $counts['reply_5_count'];
      $reply_6_count = $counts['reply_6_count'];
      $reply_7_count = $counts['reply_7_count'];

      // 設定分頁參數
      $page = isset($_GET['page']) ? intval($_GET['page']) : 1; // 預設第一頁
      $limit = 10; // 每頁顯示 10 筆
      $offset = ($page - 1) * $limit;

      // 計算頁數
      $total_reply_pages = ceil(($reply_1_count + $reply_2_count + $reply_3_count + $reply_4_count + $reply_5_count + $reply_6_count + $reply_7_count) / $limit);
      $total_reply_1_pages = ceil($reply_1_count / $limit);
      $total_reply_2_pages = ceil($reply_2_count / $limit);
      $total_reply_3_pages = ceil($reply_3_count / $limit);
      $total_reply_4_pages = ceil($reply_4_count / $limit);
      $total_reply_5_pages = ceil($reply_5_count / $limit);
      $total_reply_6_pages = ceil($reply_6_count / $limit);
      $total_reply_7_pages = ceil($reply_7_count / $limit);
      

      // 取得所有應徵回復情形簡歷(不分頁)
      $stmt = $conn->prepare("SELECT * FROM apply ORDER BY apply_id DESC");
      $stmt->execute();
      $result = $stmt->get_result();
      $reply_allpage_data = [];
      while ($row = $result->fetch_assoc()) {
         //處理 NULL 值
         $row["apply_skill"] = $row["apply_skill"] ?? '';
         $row["apply_interview"] = $row["apply_interview"] ?? '';
         $reply_allpage_data[] = $row;
      }

      // 取得所有應徵回復情形簡歷(分頁)
      $stmt = $conn->prepare("SELECT * FROM apply ORDER BY apply_id DESC LIMIT ? OFFSET ?");
      $stmt->bind_param("ii", $limit, $offset);
      $stmt->execute();
      $result = $stmt->get_result();
      $reply_all_data = [];
      while ($row = $result->fetch_assoc()) {
         //處理 NULL 值
         $row["apply_skill"] = $row["apply_skill"] ?? '';
         $row["apply_interview"] = $row["apply_interview"] ?? '';
         $reply_all_data[] = $row;
      }

      // 初始化分類資料
      $reply_1_data = [];
      $reply_2_data = [];
      $reply_3_data = [];
      $reply_4_data = [];
      $reply_5_data = [];
      $reply_6_data = [];
      $reply_7_data = [];
      
      // 取得應徵回復情形簡歷(分類)(分頁)
      for ($i = 1; $i <= 7; $i++) {
         $stmt = $conn->prepare("SELECT * FROM apply WHERE apply_reply = ? ORDER BY apply_id DESC LIMIT ? OFFSET ?");
         $stmt->bind_param("iii", $i, $limit, $offset);

         if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
               // 處理 NULL 值
               $row["apply_skill"] = $row["apply_skill"] ?? '';
               $row["apply_interview"] = $row["apply_interview"] ?? '';
                
               // 根據 reply 值存入對應的變數
               switch ($i) {
                  case 1:
                     $reply_1_data[] = $row;
                     break;
                  case 2:
                     $reply_2_data[] = $row;
                     break;
                  case 3:
                     $reply_3_data[] = $row;
                     break;
                  case 4:
                     $reply_4_data[] = $row;
                     break;
                  case 5:
                     $reply_5_data[] = $row;
                     break;
                  case 6:
                     $reply_6_data[] = $row;
                     break;
                  case 7:
                     $reply_7_data[] = $row;
                     break;
                }
            }
         } else {
            // 錯誤處理
            error_log("SQL Error: " . $stmt->error);
         }
      }

      // 將結果回傳給前端
      respond(true, "取得徵才相關資料成功", [
         'reply_1_count' => $reply_1_count,
         'reply_2_count' => $reply_2_count,
         'reply_3_count' => $reply_3_count,
         'reply_4_count' => $reply_4_count,
         'reply_5_count' => $reply_5_count,
         'reply_6_count' => $reply_6_count,
         'reply_7_count' => $reply_7_count,
         'total_reply_pages' => $total_reply_pages,
         'total_reply_1_pages' => $total_reply_1_pages,
         'total_reply_2_pages' => $total_reply_2_pages,
         'total_reply_3_pages' => $total_reply_3_pages,
         'total_reply_4_pages' => $total_reply_4_pages,
         'total_reply_5_pages' => $total_reply_5_pages,
         'total_reply_6_pages' => $total_reply_6_pages,
         'total_reply_7_pages' => $total_reply_7_pages,
         'current_page' => $page,
         'reply_1_data' => $reply_1_data,
         'reply_2_data' => $reply_2_data,
         'reply_3_data' => $reply_3_data,
         'reply_4_data' => $reply_4_data,
         'reply_5_data' => $reply_5_data,
         'reply_6_data' => $reply_6_data,
         'reply_7_data' => $reply_7_data,
         'reply_all_data' => $reply_all_data,
         'reply_allpage_data' => $reply_allpage_data,
      ]);

      $stmt->close();
      $conn->close();
   }

   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_GET['action'] ?? '';
      switch ($action) {
         case 'add':
            add_apply();
            break;
         case 'update':
            update_apply();
            break;
         default:
            respond(false, "無效的操作");
      }
   } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $action = $_GET['action'] ?? '';
      switch ($action) {
         case 'getalldata':
            get_apply_data();
            break;
         default:
            respond(false, "無效的操作");
      }
   } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
      $action = $_GET['action'] ?? '';
      switch ($action) {
         case 'delete':
            delete_apply();
            break;
         default:
            respond(false, "無效的操作");
      }
   } else {
      respond(false, "無效的請求方法");
   }
?>
