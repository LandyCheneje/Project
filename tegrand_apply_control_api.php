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

   function add_apply()
   {
      $input = get_json_input();
      if (isset($input["apply_name"], $input["apply_phone"], $input["apply_email"], $input["apply_education"], $input["apply_experience"], $input["apply_position"])) {
         $p_name = $input["apply_name"];
         $p_phone = $input["apply_phone"];
         $p_email = $input["apply_email"];
         $p_education = $input["apply_education"];
         $p_experience = $input["apply_experience"];
         $p_position = $input["apply_position"];
         $p_skill   = isset($input["apply_skill"]) && $input["apply_skill"] !== "" ? $input["apply_skill"] : null;
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
      if (isset($input["apply_name"], $input["apply_phone"], $input["apply_email"], $input["apply_education"], $input["apply_experience"], $input["apply_position"])) {
         $p_id         = trim($input["apply_id"]);
         $p_name       = trim($input["apply_name"]);
         $p_phone      = trim($input["apply_phone"]);
         $p_email      = trim($input["apply_email"]);
         $p_education  = trim($input["apply_education"]);
         $p_experience = trim($input["apply_experience"]);
         $p_position   = trim($input["apply_position"]);
         $p_skill      = isset($input["apply_skill"]) ? trim($input["apply_skill"]) : null;

         if ($p_id && $p_name && $p_phone && $p_email && $p_education && $p_experience && $p_position) {
            $conn = create_connection();

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

            if (
               $p_name === $originalData['apply_name'] && $p_phone === $originalData['apply_phone'] && $p_email === $originalData['apply_email'] && $p_education === $originalData['apply_education'] && 
               $p_experience === $originalData['apply_experience'] && 
               $p_position === $originalData['apply_position'] && $p_skill === $originalData['apply_skill']
            ){ 
               respond(false, "沒有任何變更");
               return;
            }

            $stmt = $conn->prepare("UPDATE apply SET apply_name = ?, apply_phone =?, apply_email =?, apply_education = ?, apply_experience = ?, apply_position = ?, apply_skill = ?  WHERE apply_id = ?");
            $stmt->bind_param("sssssssi", $p_name, $p_phone, $p_email, $p_education, $p_experience, $p_position, $p_skill, $p_id);

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

   function delete_apply()
   {
      $input = get_json_input();
      if (isset($input["ids"]) && is_array($input["ids"])) {
         $ids = $input["ids"];

         if (count($ids) > 0) {
            $conn = create_connection();

            $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("DELETE FROM apply WHERE apply_id IN ($ids_placeholder)");

            $types = str_repeat('i', count($ids));
            $stmt->bind_param($types, ...$ids);

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

   function get_apply_data()
   {
      $conn = create_connection();

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

      $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
      $limit = 10;
      $offset = ($page - 1) * $limit;

      $total_reply_pages = ceil(($reply_1_count + $reply_2_count + $reply_3_count + $reply_4_count + $reply_5_count + $reply_6_count + $reply_7_count) / $limit);
      $total_reply_1_pages = ceil($reply_1_count / $limit);
      $total_reply_2_pages = ceil($reply_2_count / $limit);
      $total_reply_3_pages = ceil($reply_3_count / $limit);
      $total_reply_4_pages = ceil($reply_4_count / $limit);
      $total_reply_5_pages = ceil($reply_5_count / $limit);
      $total_reply_6_pages = ceil($reply_6_count / $limit);
      $total_reply_7_pages = ceil($reply_7_count / $limit);
      
      $stmt = $conn->prepare("SELECT * FROM apply ORDER BY apply_id DESC");
      $stmt->execute();
      $result = $stmt->get_result();
      $reply_allpage_data = [];
      while ($row = $result->fetch_assoc()) {
         $row["apply_skill"] = $row["apply_skill"] ?? '';
         $row["apply_interview"] = $row["apply_interview"] ?? '';
         $reply_allpage_data[] = $row;
      }

      $stmt = $conn->prepare("SELECT * FROM apply ORDER BY apply_id DESC LIMIT ? OFFSET ?");
      $stmt->bind_param("ii", $limit, $offset);
      $stmt->execute();
      $result = $stmt->get_result();
      $reply_all_data = [];
      while ($row = $result->fetch_assoc()) {
         $row["apply_skill"] = $row["apply_skill"] ?? '';
         $row["apply_interview"] = $row["apply_interview"] ?? '';
         $reply_all_data[] = $row;
      }

      $reply_1_data = [];
      $reply_2_data = [];
      $reply_3_data = [];
      $reply_4_data = [];
      $reply_5_data = [];
      $reply_6_data = [];
      $reply_7_data = [];
      
      for ($i = 1; $i <= 7; $i++) {
         $stmt = $conn->prepare("SELECT * FROM apply WHERE apply_reply = ? ORDER BY apply_id DESC LIMIT ? OFFSET ?");
         $stmt->bind_param("iii", $i, $limit, $offset);

         if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
               $row["apply_skill"] = $row["apply_skill"] ?? '';
               $row["apply_interview"] = $row["apply_interview"] ?? '';
                
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
            error_log("SQL Error: " . $stmt->error);
         }
      }

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
