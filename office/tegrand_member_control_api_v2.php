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

   const DB_SERVER   = "ftpupload.net";
   const DB_USERNAME = "if0_38646797";
   const DB_PASSWORD = "daisy1131021";
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

   //會員註冊
   // {"username" : "owner01", "password" : "123456", "email" : "owner01@test.com"}
   // {"state" : true, "message" : "註冊成功"}
   // {"state" : false, "message" : "新增失敗與相關錯誤訊息"}
   // {"state" : false, "message" : "欄位錯誤"}
   // {"state" : false, "message" : "欄位不得為空"}
   function register_user()
   {
      $input = get_json_input();
      if (isset($input["username"], $input["password"], $input["email"])) {
         // 必填項目
         $p_username = $input["username"];
         $p_password = password_hash(trim($input["password"]), PASSWORD_DEFAULT);
         $p_email    = trim($input["email"]);

         // 選填項目
         $p_city   = isset($input["city"]) && $input["city"] !== "" ? $input["city"] : null;
         $p_area   = isset($input["area"]) && $input["area"] !== "" ? $input["area"] : null;
         $p_address   = isset($input["address"]) && $input["address"] !== "" ? $input["address"] : null;
         $p_phone     = isset($input["phone"]) && $input["phone"] !== "" ? $input["phone"] : null;
         $p_telephone = isset($input["telephone"]) && $input["telephone"] !== "" ? $input["telephone"] : null;
         $p_fax       = isset($input["fax"]) && $input["fax"] !== "" ? $input["fax"] : null;

         // 預設註冊的會員角色為1
         $p_role = 1;

         if ($p_username && $p_password && $p_email) {
            $conn = create_connection();

            $stmt = $conn->prepare("INSERT INTO users(username, password, email, city, area, address, phone, telephone, fax, role) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssi", $p_username, $p_password, $p_email, $p_city, $p_area, $p_address, $p_phone, $p_telephone, $p_fax, $p_role);

            if ($stmt->execute()) {
               respond(true, "註冊成功<br><span class='text-primary small'>將跳轉主頁，請以新註冊之帳號登入!</span>");
            } else {
               respond(false, "註冊失敗");
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

   //管理員註冊
   // {"username" : "owner01", "password" : "123456", "email" : "owner01@test.com"}
   // {"state" : true, "message" : "註冊成功"}
   // {"state" : false, "message" : "新增失敗與相關錯誤訊息"}
   // {"state" : false, "message" : "欄位錯誤"}
   // {"state" : false, "message" : "欄位不得為空"}
   function register_manager()
   {
      $input = get_json_input();
      if (isset($input["username"], $input["password"], $input["email"])) {
         // 必填項目
         $p_username = $input["username"];
         $p_password = password_hash(trim($input["password"]), PASSWORD_DEFAULT);
         $p_email    = trim($input["email"]);

         // 選填項目
         $p_city   = isset($input["city"]) && $input["city"] !== "" ? $input["city"] : null;
         $p_area   = isset($input["area"]) && $input["area"] !== "" ? $input["area"] : null;
         $p_address   = isset($input["address"]) && $input["address"] !== "" ? $input["address"] : null;
         $p_phone     = isset($input["phone"]) && $input["phone"] !== "" ? $input["phone"] : null;
         $p_telephone = isset($input["telephone"]) && $input["telephone"] !== "" ? $input["telephone"] : null;
         $p_fax       = isset($input["fax"]) && $input["fax"] !== "" ? $input["fax"] : null;

         // 預設註冊的會員角色為1
         $p_role = 0;

         if ($p_username && $p_password && $p_email) {
            $conn = create_connection();

            $stmt = $conn->prepare("INSERT INTO users(username, password, email, city, area, address, phone, telephone, fax, role) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssi", $p_username, $p_password, $p_email, $p_city, $p_area, $p_address, $p_phone, $p_telephone, $p_fax, $p_role);

            if ($stmt->execute()) {
               respond(true, "註冊成功");
            } else {
               respond(false, "註冊失敗");
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

   // 會員登入
   // {"username" : "owner01", "password" : "123456"}
   // {"state" : true, "message" : "登入成功", "data" : "使用者資訊"}
   // {"state" : false, "message" : "登入失敗與相關錯誤訊息"}
   // {"state" : false, "message" : "欄位錯誤"}
   // {"state" : false, "message" : "欄位不得為空"}
   function login_user()
   {
      $input = get_json_input();
      if (isset($input["username"], $input["password"])) {
         $p_username = trim($input["username"]);
         $p_password = trim($input["password"]);
         if ($p_username && $p_password) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $p_username); //一定要傳遞變數
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
               //抓取密碼執行password_verify比對
               $row = $result->fetch_assoc();

               $stored_password = $row["password"];
               if (password_get_info($stored_password)['algoName'] != 'unknown') {
                  // 如果已加密，使用password_verify來驗證
                  if (password_verify($p_password, $stored_password)) {
                     // 比對成功
                     $uid01       = substr(hash('sha256', time()), 10, 4) . substr(bin2hex(random_bytes(16)), 4, 4);
                     $update_stmt = $conn->prepare("UPDATE users SET uid01 = ? WHERE username = ?");
                     $update_stmt->bind_param('ss', $uid01, $p_username);
                     if ($update_stmt->execute()) {
                        $user_stmt = $conn->prepare("SELECT username, email, city, area, address, phone, telephone, fax, role, uid01, Created_at FROM users WHERE username = ?");
                        $user_stmt->bind_param("s", $p_username);
                        $user_stmt->execute();
                        $user_data = $user_stmt->get_result()->fetch_assoc();
                        respond(true, "登入成功", $user_data);
                     } else {
                        respond(false, "登入失敗, UID更新失敗");
                     }
                  } else {
                     // 密碼錯誤
                     respond(false, "登入失敗, 密碼錯誤");
                  }
               } else {
                  // 如果密碼未加密，直接比較明文密碼
                  if ($p_password === $stored_password) {
                     // 比對成功
                     $uid01       = substr(hash('sha256', time()), 10, 4) . substr(bin2hex(random_bytes(16)), 4, 4);
                     $update_stmt = $conn->prepare("UPDATE users SET uid01 = ? WHERE username = ?");
                     $update_stmt->bind_param('ss', $uid01, $p_username);
                     if ($update_stmt->execute()) {
                        $user_stmt = $conn->prepare("SELECT username, email, city, area, address, phone, telephone, fax, role, uid01, Created_at FROM users WHERE Username = ?");
                        $user_stmt->bind_param("s", $p_username);
                        $user_stmt->execute();
                        $user_data = $user_stmt->get_result()->fetch_assoc();
                        respond(true, "登入成功", $user_data);
                     } else {
                        respond(false, "登入失敗, UID更新失敗");
                     }
                  } else {
                     // 密碼錯誤
                     respond(false, "登入失敗, 密碼錯誤");
                  }
               }
            } else {
               respond(false, "登入失敗, 該帳號不存在");
            }
            $stmt->close();
            $conn->close();
         } else {
            respond(false, "登入失敗, 欄位不得為空");
         }
      } else {
         respond(false, "登入失敗, 欄位錯誤");
      }
   }

   // Uid01驗證
   // {"uid01" : "owner01"}
   // {"state" : true, "message" : "驗證成功", "data" : "使用者資訊"}
   // {"state" : false, "message" : "驗證失敗與相關錯誤訊息"}
   // {"state" : false, "message" : "欄位錯誤"}
   // {"state" : false, "message" : "欄位不得為空"}
   function check_uid()
   {
      $input = get_json_input();
      if (isset($input["uid01"])) {
         $p_uid = trim($input["uid01"]);
         if ($p_uid) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT id, username, password, email, city, area, address, phone, telephone, fax, role, uid01, created_at FROM users WHERE uid01 = ?");
            $stmt->bind_param("s", $p_uid); //一定要傳遞變數
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
               //驗證成功
               $userdata = $result->fetch_assoc();
               respond(true, "驗證成功", $userdata);
            } else {
               respond(false, "驗證失敗");
            }
            $stmt->close();
            $conn->close();
         } else {
            respond(false, "欄位不得為空");
            // respond(false, "UID 不得為空");
         }
      } else {
         respond(false, "欄位錯誤");
         // respond(false, "缺少 UID");
      }
   }

   //驗證帳號是否已經存在(給註冊介面使用)
   function check_uni_username()
   {
      $input = get_json_input();
      if (isset($input["username"])) {
         $p_username = trim($input["username"]);
         if ($p_username) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
            $stmt->bind_param("s", $p_username); //一定要傳遞變數
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
               //帳號已存在
               respond(false, "❌帳號已存在，不可使用");
            } else {
               //帳號不存在
               respond(true, "✔️帳號不存在, 可以使用");
            }
            $stmt->close();
            $conn->close();
         } else {
            respond(false, "⚠️欄位不得為空");
         }
      } else {
         respond(false, "⚠️欄位錯誤");
      }
   }

   function update_user()
   {
      $input = get_json_input();
      if (isset($input["id"], $input["email"])) {  //只檢查id和email 必填
         $p_id        = trim($input["id"]);
         $p_email     = trim($input["email"]);
         $p_city   = isset($input["city"]) ? trim($input["city"]) : null;
         $p_area   = isset($input["area"]) ? trim($input["area"]) : null;
         $p_address   = isset($input["address"]) ? trim($input["address"]) : null;
         $p_phone     = isset($input["phone"]) ? trim($input["phone"]) : null;
         $p_telephone = isset($input["telephone"]) ? trim($input["telephone"]) : null;
         $p_fax       = isset($input["fax"]) ? trim($input["fax"]) : null;


         if ($p_id && $p_email) {
            $conn = create_connection();

            // 先查詢目前的資料
            $stmt = $conn->prepare("SELECT email, city, area, address, phone, telephone, fax FROM users WHERE id = ?");
            $stmt->bind_param("i", $p_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $originalData = $result->fetch_assoc();
            $stmt->close();

            if (!$originalData) {
               respond(false, "找不到該用戶");
               return;
            }

            // 比對資料是否有變更
            if (
               $p_email === $originalData['email'] &&
               $p_city === $originalData['city'] &&
               $p_area === $originalData['area'] &&
               $p_address === $originalData['address'] &&
               $p_phone === $originalData['phone'] &&
               $p_telephone === $originalData['telephone'] &&
               $p_fax === $originalData['fax']
            ) {
               respond(false, "沒有任何變更");
               return;
            }

            //執行更新
            $stmt = $conn->prepare("UPDATE users SET email = ?, city = ?, area = ?, address = ?, phone = ?, telephone = ?, fax = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $p_email, $p_city, $p_area, $p_address, $p_phone, $p_telephone, $p_fax, $p_id); //一定要傳遞變數

            if ($stmt->execute()) {
               if ($stmt->affected_rows === 1) {
                  respond(true, "會員更新成功");
               } else {
                  respond(false, "會員更新失敗, 並無更新行為!");
               }
            } else {
               respond(false, "會員更新失敗");
            }
            $stmt->close();
            $conn->close();
         } else {
            respond(false, "Email為必填項目");
         }
      } else {
         respond(false, "欄位錯誤");
      }
   }

   // 會員刪除
   // {"id" : "xxxxxx"}
   // {"state" : true, "message" : "會員刪除成功"}
   // {"state" : false, "message" : "會員刪除失敗與相關錯誤訊息"}
   // {"state" : false, "message" : "欄位錯誤"}
   // {"state" : false, "message" : "欄位不得為空白"}
   function delete_user()
   {
      $input = get_json_input();
      if (isset($input["id"])) {
         $p_id = trim($input["id"]);
         if ($p_id) {
            $conn = create_connection();

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $p_id); //一定要傳遞變數

            if ($stmt->execute()) {
               if ($stmt->affected_rows === 1) {
                  respond(true, "會員刪除成功");
               } else {
                  respond(false, "會員刪除失敗, 並無刪除行為!");
               }
            } else {
               respond(false, "會員刪除失敗");
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

   //批量會員刪除
   function delete_selected_user()
   {
      $input = get_json_input();
      if (isset($input["ids"]) && is_array($input["ids"])) {
         $ids = $input["ids"];  // 這是包含所有 ID 的陣列

         if (count($ids) > 0) {
            $conn = create_connection();

            // 用於批量刪除的 SQL 語句
            $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($ids_placeholder)");

            // 綁定參數
            $types = str_repeat('i', count($ids));  // 所有參數都是整數型別
            $stmt->bind_param($types, ...$ids);  // 傳遞參數

            if ($stmt->execute()) {
               if ($stmt->affected_rows > 0) {
                  respond(true, "選中的會員已成功刪除");
               } else {
                  respond(false, "沒有任何會員被刪除");
               }
            } else {
               respond(false, "刪除操作失敗");
            }
            $stmt->close();
            $conn->close();
         } else {
            respond(false, "沒有選擇會員進行刪除");
         }
      } else {
         respond(false, "欄位錯誤");
      }
   }

   //取得會員(role=1)、管理員(role=0)的資料、數量、分頁資料
   function get_user_data()
   {
      $conn = create_connection();

      // 取得會員(role=1)和管理員(role=0)數量
      $stmt = $conn->prepare("SELECT 
               (SELECT COUNT(*) FROM users WHERE role = 1) AS member_count,
               (SELECT COUNT(*) FROM users WHERE role = 0) AS manager_count");
      $stmt->execute();
      $result = $stmt->get_result();
      $counts = $result->fetch_assoc();
      $member_count = $counts['member_count'];
      $manager_count = $counts['manager_count'];

      // 設定分頁參數
      $page = isset($_GET['page']) ? intval($_GET['page']) : 1; // 預設第一頁
      $limit = 10; // 每頁顯示 10 筆
      $offset = ($page - 1) * $limit;

      // 計算總頁數（只對會員 role=1 計算）
      $total_member_pages = ceil($member_count / $limit);
      $total_manager_pages = ceil($manager_count / $limit);

      // 查詢當前頁面的會員資料
      // $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE role = 1 LIMIT ? OFFSET ?");
      // $stmt->bind_param("ii", $limit, $offset);
      // $stmt->execute();
      // $result = $stmt->get_result();

      // 取得當前頁會員(role=1)資料
      $stmt = $conn->prepare("SELECT id, username, password, email, city, area, address, phone, telephone, fax FROM users WHERE role = 1 ORDER BY id DESC LIMIT ? OFFSET ?");
      $stmt->bind_param("ii", $limit, $offset);
      $stmt->execute();
      $result = $stmt->get_result();

      $member = [];
      while ($row = $result->fetch_assoc()) {
         //處理 NULL 值
         $row["city"]      = $row["city"] ?? '';
         $row["area"]      = $row["area"] ?? '';
         $row["address"]   = $row["address"] ?? '';
         $row["phone"]     = $row["phone"] ?? '';
         $row["telephone"] = $row["telephone"] ?? '';
         $row["fax"]       = $row["fax"] ?? '';

         $member[] = $row;
      }

      // 取得當前頁管理員(role=0)資料
      $stmt = $conn->prepare("SELECT id, username, password, email, city, area, address, phone, telephone, fax FROM users WHERE role = 0 ORDER BY id DESC LIMIT ? OFFSET ?");
      $stmt->bind_param("ii", $limit, $offset);
      $stmt->execute();
      $result = $stmt->get_result();

      $manager = [];
      while ($row = $result->fetch_assoc()) {
         //處理 NULL 值
         $row["city"]      = $row["city"] ?? '';
         $row["area"]      = $row["area"] ?? '';
         $row["address"]   = $row["address"] ?? '';
         $row["phone"]     = $row["phone"] ?? '';
         $row["telephone"] = $row["telephone"] ?? '';
         $row["fax"]       = $row["fax"] ?? '';

         $manager[] = $row;
      }

      // 將結果回傳給前端
      respond(true, "取得使用者相關資料成功", [
         'member_count' => $member_count,
         'manager_count' => $manager_count,
         'total_member_pages' => $total_member_pages,
         'total_manager_pages' => $total_manager_pages,
         'current_page' => $page,
         'member' => $member,
         'manager' => $manager,
      ]);

      $stmt->close();
      $conn->close();
   }

   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_GET['action'] ?? '';
      switch ($action) {
         case 'register':
            register_user();
            break;
         case 'register_0':
            register_manager();
            break;
         case 'login':
            login_user();
            break;
         case 'checkuid':
            check_uid();
            break;
         case 'checkuni':
            check_uni_username();
            break;
         case 'update':
            update_user();
            break;
         default:
            respond(false, "無效的操作");
      }
   } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $action = $_GET['action'] ?? '';
      switch ($action) {
         case 'getalldata':
            get_user_data();
            break;
         default:
            respond(false, "無效的操作");
      }
   } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
      $action = $_GET['action'] ?? '';
      switch ($action) {
         case 'delete':
            delete_user();
            break;
         case 'delete_selected':
            delete_selected_user();
            break;
         default:
            respond(false, "無效的操作");
      }
   } else {
      respond(false, "無效的請求方法");
   }
?>
