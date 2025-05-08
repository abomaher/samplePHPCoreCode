<?php
/**
 * Author name: Abdulhakim Zuqut
 * Author contact: 00966555413337
 * Author linkedin: https://sa.linkedin.com/in/abdulhakim-zuqut
 */


class Students extends Admission {

    public $student_id;
    public $student_academic_id;
    public $student_password;
    public $student_error;

    public $program_id;
    public $new_semester_id;
    public $student_photo;


    public $file_type = array('pdf','png', 'jpg', 'jpeg');
    public $target_dir = "uploads/";
    public $target_dir_admin = "../uploads/";
    public $upload_size = 5000000;



    public function get_all_student($status=''){

        global $database;

        $status = (!empty($status)) ? "WHERE status='$status'" : "";
        $sql = "SELECT * FROM students $status";

        return $database->query($sql);

    }

    public static function verify_student($pass, $studentId){

        global $database;

        $pass = $database->escape_string($pass);

        $passMd5 =  md5(md5($pass));

        $sql = "SELECT * FROM students WHERE student_academic_id = '$studentId' AND password = '$passMd5' LIMIT 1";

        $the_result_array = $database->query($sql);

        return !empty($the_result_array) ? $the_result_array : false ;


    }

    public function get_student($by='', $v='', $not_in=''){


        global $database;

        $b = "";

        if(!empty($by)){

            $id = (!empty($not_in)) ? "AND id!='$not_in'" : '';

            if($by == 'email'){

                $b = "WHERE email='$v' $id";

            }elseif($by == 'mobile'){

                $b = "WHERE mobile='$v' $id";

            }elseif($by == 'national_passport'){

                $b = "WHERE national_passport='$v' $id";

            }else{

                $b = "";

            }

        }

        $sql = "SELECT * FROM students $b";

        return $database->query($sql);

    }



    public function add(){

        global $database;
        global $setting;
        global $t;
        global $admission;

        $filed = $this->filed;
        $year = date('Y');
        $semester = $setting->get_setting('semester');
        $add_date = $database->now_date();
        $up_date = $database->now_date();

        $status = 'active';

        $name_en = $filed['your_first_name_en'] . " " . $filed['your_second_name_en'] . " " . $filed['your_last_name_en'];
        $name_ar = $filed['your_first_name_ar'] . " " . $filed['your_second_name_ar'] . " " . $filed['your_last_name_ar'];
        $email = $filed['your_email'];
        $mobile = $filed['mobile_number'];
        $national_passport = $filed['national_id_passport_number'];

        if(mysqli_num_rows($this->get_student('email', $email)) == 0){

            if(mysqli_num_rows($this->get_student('mobile', $mobile)) == 0){

                if(mysqli_num_rows($this->get_student('national_passport', $national_passport)) == 0){

                    $sql = "INSERT INTO students (name_en, name_ar, email, mobile, national_passport, register_semester, register_year, status, register_date, up_date) VALUES ";
                    $sql .= "('". $name_en ."', ";
                    $sql .= "'". $name_ar ."', ";
                    $sql .= "'". $email ."', ";
                    $sql .= "'". $mobile ."', ";
                    $sql .= "'". $national_passport ."', ";
                    $sql .= "'". $semester ."', ";
                    $sql .= "'". $year ."', ";
                    $sql .= "'". $status ."', ";
                    $sql .= "'". $add_date ."', ";
                    $sql .= "'". $up_date ."'";
                    $sql .= ")";

                    $add = $database->query($sql) or die(mysqli_error($database->connection));

                    if($add){

                        $id = $this->student_id = $database->the_insert_id();

                        $pas2 = md5(md5($national_passport));
                        $st_id = $year . $semester . $id;
                        $database->query("UPDATE students SET student_academic_id = '$st_id', password='$pas2' WHERE id = '$id'");

                        $this->student_academic_id = $st_id;
                        $this->student_password = $national_passport;

                        foreach ($this->filed as $k => $v){
                            $add2 = $this->add_students_filed($id, $k, $v);
                        }

                        $sql2 = "INSERT INTO academic_programs_students (student_id, program_id, admission_semester, admission_year, register_date) VALUES ";
                        $sql2 .= "('". $id ."', ";
                        $sql2 .= "'". $this->filed['program'] ."', ";
                        $sql2 .= "'". $semester ."', ";
                        $sql2 .= "'". $year ."', ";
                        $sql2 .= "'". $add_date ."'";
                        $sql2 .= ")";

                        $add3 = $database->query($sql2) or die(mysqli_error($database->connection));

                        if($add2 && $add3){
                            return true;
                        }else{

                            $database->query("DELETE FROM students WHERE id = '$id'");
                            $database->query("DELETE FROM students_filed WHERE students_id = '$id'");

                            $this->student_error = $admission->admission_error_msg = "nooooo";
                            return false;
                        }

                    }else{
                        return false;
                    }

                }else{

                    $this->student_error = $admission->admission_error_msg = $t['national_passport_not_available_for_student'];
                    return false;

                }

            }else{

                $this->student_error = $admission->admission_error_msg = $t['mobile_not_available_for_student'];
                return false;

            }

        }else{

            $this->student_error = $admission->admission_error_msg = $t['email_not_available_for_student'];
            return false;

        }

    }

    public function add_students_filed($id, $name, $value){

        global $database;

        $sql = "INSERT INTO students_filed (student_id, name, value) VALUES ";
        $sql .= "('". $id ."', ";
        $sql .= "'". $name ."', ";
        $sql .= "'". $value ."')";

        $add = $database->query($sql);

        return $add;

    }

    public function get_students_filed($id, $name){

        global $database;

        $sql = "SELECT * FROM students_filed WHERE name='$name' AND student_id='$id'";

        $filed = $database->query($sql);

        return (mysqli_num_rows($filed) != 0) ? mysqli_fetch_assoc($filed)['value'] : '';

    }

    public function get_student_by_id($id){

        global $database;

        $sql = "SELECT * FROM students WHERE id='$id'";

        return $database->query($sql);

    }

    public function add_student_after_payment_admission($admission_id){

        $get_filed = $this->get_all_admission_filed($admission_id);

        $filed = [];

        while ($f = mysqli_fetch_assoc($get_filed)){
            $filed[$f['name']] = $f['value'];
        }

        $this->filed = $filed;

        return $this->add();

    }

    public function get_student_last_semester_program($student_id, $program_id){

        global $database;

        $sql = $database->query("SELECT * FROM students_semesters WHERE program_id='$program_id' AND student_id = '$student_id' ORDER BY id DESC LIMIT 1");

        return $sql;

    }

    public function add_student_semester($student_id, $program_id){

        global $database;
        global $programs;
        global $t;
        global $admission;
        global $setting;

        $this->student_id = $student_id;
        $this->program_id = $program_id;

        $get_program_semesters = $programs->get_program_semesters($program_id);

        if(mysqli_num_rows($get_program_semesters) != 0) {
            $get_last_semester = $this->get_student_last_semester_program($student_id, $program_id);

            $program_semesters_count = mysqli_num_rows($get_program_semesters);

            if (mysqli_num_rows($get_last_semester) != 0) {
                $get_last_semester_arr = mysqli_fetch_assoc($get_last_semester);
                $semester_status = $get_last_semester_arr['status'];

                if ($semester_status == 'done') {

                    $semester_id = $get_last_semester_arr['program_semester_id'];
                    $get_semester = $programs->get_semester_by_id($semester_id);

                    if(mysqli_num_rows($get_semester) != 0){

                        $semester_sort = mysqli_fetch_assoc($get_semester)['sort'];

                        if($semester_sort < $program_semesters_count){


                            $get_semester_id = $programs->get_program_semesters_by_sort($program_id, ($semester_sort + 1));
                            $this->new_semester_id = mysqli_fetch_assoc($get_semester_id)['id'];

                            return $this->add_semester();


                        }else{

                            $this->student_error = $admission->admission_error_msg = $this->admission_error_msg = $t['error_student_registered_last_program_semester'];
                            return false;

                        }

                    }else{
                        $this->student_error = $admission->admission_error_msg = $t['error_not_found_program_semester'];
                        return false;
                    }


                } else {
                    $this->student_error = $admission->admission_error_msg = $t['error_not_finish_last_program_semester'];
                    return false;
                }
            } else {

                $get_semester_id = $programs->get_program_semesters_by_sort($program_id, 1);
                $this->new_semester_id = mysqli_fetch_assoc($get_semester_id)['id'];

                return $this->add_semester();

            }
        }else{

            $this->student_error = $admission->admission_error_msg = $t['error_not_found_program_semesters'];
            return false;

        }


    }

    public function add_semester(){

        global $database;
        global $programs;
        global $t;
        global $setting;
        global $admission;


        $add_date = $database->now_date();
        $register_year = date('Y');
        $register_semester = $setting->get_setting('semester');

        $sql = "INSERT INTO students_semesters (student_id, program_id, program_semester_id, register_semester, register_year, add_date) VALUES ";
        $sql .= "('". $this->student_id ."', ";
        $sql .= "'". $this->program_id ."', ";
        $sql .= "'". $this->new_semester_id ."', ";
        $sql .= "'". $register_semester ."', ";
        $sql .= "'". $register_year ."', ";
        $sql .= "'". $add_date ."'";
        $sql .= ")";

        $add_sm2 = $database->query($sql) or die(mysqli_error($database->connection));

        if($add_sm2){

            $new_student_semester_id = $database->the_insert_id();

            $get_courses = $programs->get_semester_courses($this->new_semester_id, $this->program_id);

            if(mysqli_num_rows($get_courses) != 0){

                $cou_ids = array();

                while ($cou = mysqli_fetch_assoc($get_courses)){

                    $course_id = $cou['id'];

                    $sql2 = "INSERT INTO students_courses (student_id, course_id, program_semester_id, program_id, register_year, register_semester) VALUES ";
                    $sql2 .= "('". $this->student_id ."', ";
                    $sql2 .= "'". $course_id ."', ";
                    $sql2 .= "'". $this->new_semester_id ."', ";
                    $sql2 .= "'". $this->program_id ."', ";
                    $sql2 .= "'". $register_year ."', ";
                    $sql2 .= "'". $register_semester ."'";
                    $sql2 .= ")";

                    $add_c = $database->query($sql2) or die(mysqli_error($database->connection));

                    $cou_ids[] = $database->the_insert_id();

                }

                if($add_c){

                    return true;

                }else{

                    $database->query("DELETE FROM students_semesters WHERE id = '$new_student_semester_id'");

                    foreach($cou_ids as $ci){

                        $database->query("DELETE FROM students_courses WHERE id = '$ci'");

                    }
                    $this->student_error = $admission->admission_error_msg = $t['something_warning_try_again_add_courses'];
                    return false;

                }


            }else{

                $database->query("DELETE FROM students_semesters WHERE id = '$new_student_semester_id'");
                $this->student_error = $admission->admission_error_msg = $t['error_not_found_courses_for_semester'];
                return false;

            }



        }else{

            $this->student_error = $admission->admission_error_msg = $t['something_warning_try_again_add_semester'];
            return false;

        }


    }

    public static function find_student_by_email($email, $id_not_in=''){

        global $database;

        $email = $database->escape_string($email);

        $u = (!empty($id_not_in)) ? "AND id!='$id_not_in'" : '';
        $the_result_array = $database->query("SELECT * FROM students WHERE email = '$email' $u");

        return $the_result_array;

    }

    public static function find_student_forgot_pass($email){

        global $database;

        $email = $database->escape_string($email);

        $the_result_array = $database->query("SELECT * FROM forgot_pass WHERE email = '$email' AND status = 1");

        return (mysqli_num_rows($the_result_array) != 0) ? true : false;

    }

    public static function forgot_pass($email , $student_id){

        global $database;

        $add_date = $database->now_date();
        $the_token = md5($email . $student_id);

        $sql = "INSERT INTO forgot_pass (student_id, email, token, status, add_date) VALUES ('$student_id', '$email', '$the_token', 1, '$add_date')";

        $the_insert_array = $database->query($sql);

        return ($the_insert_array) ? $the_token : false;

    }

    public static function update_student_pass($email, $pass, $token){

        global $database;

        $pass = $database->escape_string(md5(md5($pass)));
        $email = $database->escape_string($email);

        $up_user = $database->query("UPDATE students SET password = '$pass' WHERE email='$email'");

        if($up_user){
            $student = mysqli_fetch_assoc(self::find_student_by_email($email));
            add_log($student['id'], $student['name'], 'Reset password', $student['type']);
            $up_token_status = $database->query("UPDATE forgot_pass SET status = 0 WHERE token = '$token'");
            return $up_token_status ? true : false;
        }else{
            return false;
        }

    }

    public function add_student_log($student_id, $text){

        global $database;
        global $session;

        $add_date = $database->now_date();

        $sql2 = "INSERT INTO students_log (by_name, by_id, text, date, student_id) VALUES ";
        $sql2 .= "('". User::find_user_by_id($session->user_id)['name'] ."', ";
        $sql2 .= "'". $session->user_id ."', ";
        $sql2 .= "'". $text ."', ";
        $sql2 .= "'". $add_date ."', ";
        $sql2 .= "'". $student_id ."'";
        $sql2 .= ")";

        return $database->query($sql2) or die(mysqli_error($database->connection));

    }


    public function get_student_log($student_id){

        global $database;

        $sql = "SELECT * FROM students_log WHERE student_id='$student_id' ORDER BY id DESC";

        return $database->query($sql);

    }

    public function update($id){

        global $database;

        if(!empty($this->student_password)){
            $pass = $database->escape_string(md5(md5($this->student_password)));
        }else{
            $pass = mysqli_fetch_assoc($this->get_student_by_id($id))['password'];
        }

        if(!empty($this->student_photo)){
            $photo = $this->student_photo;
        }else{
            $photo = mysqli_fetch_assoc($this->get_student_by_id($id))['photo'];
        }


        $sql = "UPDATE students SET password='$pass', photo='$photo' WHERE id='$id'";

        $up = $database->query($sql);

        if($up){

            return true;

        }else{
            return false;
        }

    }

    public function get_student_last_program($student_id){

        global $database;

        $sql = "SELECT * FROM academic_programs_students WHERE student_id='$student_id' AND status='active' ORDER BY id DESC LIMIT 1";

        return $database->query($sql);

    }

    public function get_student_last_semester($student_id, $program_id){

        global $database;

        $sql = "SELECT * FROM students_semesters WHERE program_id='$program_id' AND student_id='$student_id' ORDER BY id DESC LIMIT 1";

        return $database->query($sql);

    }

    public function get_student_semesters($student_id, $program_id){

        global $database;

        $sql = "SELECT * FROM students_semesters WHERE program_id='$program_id' AND student_id='$student_id'";

        return $database->query($sql);

    }

    public function get_students_semester_courses($student_id, $semester_id, $program_id){

        global $database;

        $sql = "SELECT * FROM students_courses WHERE program_semester_id='$semester_id' AND program_id='$program_id' AND student_id='$student_id'";

        return $database->query($sql);

    }



}

$students = new Students();