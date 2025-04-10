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

   function register_user()
   {
      $input = get_json_input();
      if (isset($input["username"], $input["password"], $input["email"])) {
         $p_username = $input["username"];
         $p_password = password_hash(trim($input["password"]), PASSWORD_DEFAULT);
         $p_email    = trim($input["email"]);

         $p_city   = isset($input["city"]) && $input["city"] !== "" ? $input["city"] : null;
         $p_area   = isset($input["area"]) && $input["area"] !== "" ? $input["area"] : null;
         $p_address   = isset($input["address"]) && $input["address"] !== "" ? $input["address"] : null;
         $p_phone     = isset($input["phone"]) && $input["phone"] !== "" ? $input["phone"] : null;
         $p_telephone = isset($input["telephone"]) && $input["telephone"] !== "" ? $input["telephone"] : null;
         $p_fax       = isset($input["fax"]) && $input["fax"] !== "" ? $input["fax"] : null;

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

   function register_manager()
   {
      $input = get_json_input();
      if (isset($input["username"], $input["password"], $input["email"])) {
         $p_username = $input["username"];
         $p_password = password_hash(trim($input["password"]), PASSWORD_DEFAULT);
         $p_email    = trim($input["email"]);

         $p_city   = isset($input["city"]) && $input["city"] !== "" ? $input["city"] : null;
         $p_area   = isset($input["area"]) && $input["area"] !== "" ? $input["area"] : null;
         $p_address   = isset($input["address"]) && $input["address"] !== "" ? $input["address"] : null;
         $p_phone     = isset($input["phone"]) && $input["phone"] !== "" ? $input["phone"] : null;
         $p_telephone = isset($input["telephone"]) && $input["telephone"] !== "" ? $input["telephone"] : null;
         $p_fax       = isset($input["fax"]) && $input["fax"] !== "" ? $input["fax"] : null;

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

   function login_user()
   {
      $input = get_json_input();
      if (isset($input["username"], $input["password"])) {
         $p_username = trim($input["username"]);
         $p_password = trim($input["password"]);
         if ($p_username && $p_password) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $p_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
               $row = $result->fetch_assoc();

               $stored_password = $row["password"];
               if (password_get_info($stored_password)['algoName'] != 'unknown') {
                  if (password_verify($p_password, $stored_password)) {
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
                     respond(false, "登入失敗, 密碼錯誤");
                  }
               } else {
                  if ($p_password === $stored_password) {
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

   function check_uid()
   {
      $input = get_json_input();
      if (isset($input["uid01"])) {
         $p_uid = trim($input["uid01"]);
         if ($p_uid) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT id, username, password, email, city, area, address, phone, telephone, fax, role, uid01, created_at FROM users WHERE uid01 = ?");
            $stmt->bind_param("s", $p_uid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
               $userdata = $result->fetch_assoc();
               respond(true, "驗證成功", $userdata);
            } else {
               respond(false, "驗證失敗");
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

   function check_uni_username()
   {
      $input = get_json_input();
      if (isset($input["username"])) {
         $p_username = trim($input["username"]);
         if ($p_username) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
            $stmt->bind_param("s", $p_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
               respond(false, "❌帳號已存在，不可使用");
            } else {
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
      if (isset($input["id"], $input["email"])) {
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

            $stmt = $conn->prepare("UPDATE users SET email = ?, city = ?, area = ?, address = ?, phone = ?, telephone = ?, fax = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $p_email, $p_city, $p_area, $p_address, $p_phone, $p_telephone, $p_fax, $p_id);

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

   function delete_user()
   {
      $input = get_json_input();
      if (isset($input["id"])) {
         $p_id = trim($input["id"]);
         if ($p_id) {
            $conn = create_connection();

            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $p_id);

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

   function delete_selected_user()
   {
      $input = get_json_input();
      if (isset($input["ids"]) && is_array($input["ids"])) {
         $ids = $input["ids"];

         if (count($ids) > 0) {
            $conn = create_connection();

            $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($ids_placeholder)");

            $types = str_repeat('i', count($ids));
            $stmt->bind_param($types, ...$ids);

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

   function get_user_data()
   {
      $conn = create_connection();

      $stmt = $conn->prepare("SELECT 
               (SELECT COUNT(*) FROM users WHERE role = 1) AS member_count,
               (SELECT COUNT(*) FROM users WHERE role = 0) AS manager_count");
      $stmt->execute();
      $result = $stmt->get_result();
      $counts = $result->fetch_assoc();
      $member_count = $counts['member_count'];
      $manager_count = $counts['manager_count'];

      $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
      $limit = 10;
      $offset = ($page - 1) * $limit;

      $total_member_pages = ceil($member_count / $limit);
      $total_manager_pages = ceil($manager_count / $limit);

      $stmt = $conn->prepare("SELECT id, username, password, email, city, area, address, phone, telephone, fax FROM users WHERE role = 1 ORDER BY id DESC LIMIT ? OFFSET ?");
      $stmt->bind_param("ii", $limit, $offset);
      $stmt->execute();
      $result = $stmt->get_result();

      $member = [];
      while ($row = $result->fetch_assoc()) {
         $row["city"]      = $row["city"] ?? '';
         $row["area"]      = $row["area"] ?? '';
         $row["address"]   = $row["address"] ?? '';
         $row["phone"]     = $row["phone"] ?? '';
         $row["telephone"] = $row["telephone"] ?? '';
         $row["fax"]       = $row["fax"] ?? '';

         $member[] = $row;
      }

      $stmt = $conn->prepare("SELECT id, username, password, email, city, area, address, phone, telephone, fax FROM users WHERE role = 0 ORDER BY id DESC LIMIT ? OFFSET ?");
      $stmt->bind_param("ii", $limit, $offset);
      $stmt->execute();
      $result = $stmt->get_result();

      $manager = [];
      while ($row = $result->fetch_assoc()) {
         $row["city"]      = $row["city"] ?? '';
         $row["area"]      = $row["area"] ?? '';
         $row["address"]   = $row["address"] ?? '';
         $row["phone"]     = $row["phone"] ?? '';
         $row["telephone"] = $row["telephone"] ?? '';
         $row["fax"]       = $row["fax"] ?? '';

         $manager[] = $row;
      }

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
