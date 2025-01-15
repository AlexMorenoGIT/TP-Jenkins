<?php
use PHPUnit\Framework\TestCase;

class RegisterTest extends TestCase
{
    private $con;

    protected function setUp(): void
    {
        // Setup test database connection
        $this->con = mysqli_connect("localhost", "test_user", "test_password", "test_db");

        // Clean up test database before each test
        mysqli_query($this->con, "TRUNCATE TABLE users");
        mysqli_query($this->con, "TRUNCATE TABLE user_wallet");
        mysqli_query($this->con, "TRUNCATE TABLE user_w_msg");

        // Start session for tests that need it
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        mysqli_close($this->con);
    }

    public function testRegisterWithEmptyName()
    {
        $_POST = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'mobile' => '1234567890',
            'name' => ''
        ];

        ob_start();
        require_once 'register.php';
        $output = ob_get_clean();

        $result = json_decode($output, true);
        $this->assertEquals(0, $result['status']);
        $this->assertEquals('Enter Name', $result['msg']);
    }

    public function testRegisterWithValidData()
    {
        $_POST = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'mobile' => '1234567890',
            'name' => 'Test User'
        ];

        ob_start();
        require_once 'register.php';
        $output = ob_get_clean();

        $result = json_decode($output, true);
        $this->assertEquals(1, $result['status']);
        $this->assertEquals('Account Created Successfully', $result['msg']);

        // Verify user was created in database
        $query = "SELECT * FROM users WHERE email='test@example.com'";
        $res = mysqli_query($this->con, $query);
        $this->assertEquals(1, mysqli_num_rows($res));

        // Verify wallet was created
        $user = mysqli_fetch_assoc($res);
        $wallet_query = "SELECT * FROM user_wallet WHERE user_id='{$user['id']}'";
        $wallet_res = mysqli_query($this->con, $wallet_query);
        $this->assertEquals(1, mysqli_num_rows($wallet_res));
    }

    public function testRegisterWithExistingEmail()
    {
        // First create a user
        mysqli_query($this->con, "INSERT INTO users (name, password, mobile, email, status) 
                                 VALUES ('Test User', 'hash', '1234567890', 'test@example.com', '1')");

        // Try to register with same email
        $_POST = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'mobile' => '0987654321',
            'name' => 'Another User'
        ];

        ob_start();
        require_once 'register.php';
        $output = ob_get_clean();

        $result = json_decode($output, true);
        $this->assertEquals(0, $result['status']);
        $this->assertEquals('Email is not available', $result['msg']);
    }

    public function testRegisterWithCartSession()
    {
        $_SESSION['USER_CART'] = ['item1', 'item2'];
        $_SESSION['CART_QTY'] = [1, 2];

        $_POST = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'mobile' => '1234567890',
            'name' => 'Test User'
        ];

        ob_start();
        require_once 'register.php';
        $output = ob_get_clean();

        // Verify session variables were unset
        $this->assertFalse(isset($_SESSION['USER_CART']));
        $this->assertFalse(isset($_SESSION['CART_QTY']));
    }
}