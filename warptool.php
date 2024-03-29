<?php 
require './Request.php';
require './Console.php';
$config = json_decode(trim(file_get_contents('config.cfg')), true);
if (empty($config)) {
    $version = rand(109,123);
    $ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/$version.0.0.0 Safari/537.36";
    $sec_ua = "\"Google Chrome\";v=\"$version\", \"Not:A-Brand\";v=\"8\", \"Chromium\";v=\"$version\"";
    $config = ['ua'=>$ua,'sec_ua'=>$sec_ua];
    file_put_contents('config.cfg', json_encode($config));
}
$request = new Request("https://client.warpcast.com/v2/user-usernames");
$request->userAgent = $ua = $config['ua'];
$header = [
    'accept: */*',
    'accept-language: vi-VN,vi;q=0.9,fr-FR;q=0.8,fr;q=0.7,en-US;q=0.6,en;q=0.5',
    'content-type: application/json; charset=utf-8',
    'origin: https://warpcast.com',
    'referer: https://warpcast.com/',
    'sec-ch-ua: '.$config['sec_ua'],
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'sec-fetch-dest: empty',
    'sec-fetch-mode: cors',
    'sec-fetch-site: same-site',
    'user-agent: ' .$ua,
];
$request->headers = $header;
date_default_timezone_set("Asia/Ho_Chi_Minh");
echo "           
\033[0;36m╔═══════════════════════════════════════════════╗
\033[0;36m║\033[0;32m                                              \033[0;36m ║                          
\033[0;36m        ╔═══════════════════════════════╗
\033[0;36m║       ║ \033[1;97m      Warpcast: DangDang     \033[0;36m ║       ║
\033[0;36m        ╚═══════════════════════════════╝
\033[0;36m║\033[0;35m                                              \033[0;36m ║
\033[0;36m╚═══════════════════════════════════════════════╝
\n";

if (!empty($config["token"])) {
    $new_header = array_merge($header, ['authorization: Bearer ' . $config["token"]]);
    $request->headers = $new_header;
    $request->setAddress("https://client.warpcast.com/v2/user-by-username?username=".$config["usernames"]);
    $request->setRequestType("GET");
    $request->execute();
    $res = json_decode($request->getResponse());
    $user = $res->result->user;

    if (!empty($res->result->user)) {
        $user = $res->result->user;
        Console::log('Chào mừng bạn quay trở lại, '.$user->displayName, "green");
        goto LOGIN_SUCCESS;
    } else {
        Console::log('Tài khoản của bạn đã không còn hiệu lực vui lòng nhập lại', 'red');
    }
}

Console::log('Hãy nhập account Warpcast của bạn vào đây');
do {
    $token = trim(fgets(STDIN));
    if ($token == "x" || $token == "X") {
        Console::log('Tạm biệt - Nếu có vẫn đề gì hãy liên hệ Warpcast: DangDang', 'green');
        die();
    }

    $new_header = array_merge($header, ['authorization: Bearer ' . $token]);

    $request->headers = $new_header;
    $request->setAddress("https://client.warpcast.com/v2/user-usernames");
    $request->setRequestType("GET");
    $request->execute();
    $res = json_decode($request->getResponse());

    if (!empty($res->result->usernames)) {
        $usernames = $res->result->usernames[0]->name;

        $request->setAddress("https://client.warpcast.com/v2/user-by-username?username=$usernames");
        $request->execute();
        $res = json_decode($request->getResponse());
        $user = $res->result->user;

        Console::log('Tuyệt vời!!! Xin chào '.$user->displayName, "green");
        $config["user"] = json_encode($user);
        $config["token"] = $token;
        $config["usernames"] = $usernames;
        file_put_contents('config.cfg', json_encode($config));
        break;
    } else {
        Console::log('Không đúng hãy nhập lại - bấm X để thoát', 'red');
    }
} while (true);

LOGIN_SUCCESS:

//Get ETH Adress
if (empty($config["eth"])) {
    $request->setAddress("https://client.warpcast.com/v2/verifications?fid=$user->fid&limit=15");
    $request->execute();
    $res = json_decode($request->getResponse());
    
    if (!empty($res->result->verifications)) {
        foreach ($res->result->verifications as $v) {
            if ($v->protocol == "ethereum") {
                $config["eth"] = $eth = $v->address;
                file_put_contents('config.cfg', json_encode($config));
            }
        }
    } else {
        Console::log('Bạn chưa nhập ví của mình vào tài khoản - hãy làm ngay để nhận airdrop nhé!', 'red');
        die();
    }
}
echo "\n";
Console::log('Ethereum adress: '. $config["eth"]);

//Get Point Degen
$degen = json_decode(file_get_contents("https://www.degen.tips/api/airdrop2/tip-allowance?address=".$config["eth"]));
$point = json_decode(file_get_contents("https://www.degen.tips/api/airdrop2/season2/points?address=".$config["eth"]));

echo "\n";
if ($degen) {
    Console::log('Rank: '. $degen[0]->user_rank, 'green');
    Console::log('Tip Allowance: '. $degen[0]->tip_allowance, 'green');
    Console::log('Remaining: '. $degen[0]->remaining_allowance, 'green');
    echo "\n";
    Console::log('Point: '. $point[0]->points, 'green');
} else {
    Console::log('hệ thống $DEGEN đang có vẫn đề nên không thế lấy được dữ liệu - không sao bạn có thế kiểm tra vào lần tới', 'green');
}

$ccs = file("./comment.txt");
$friends = file("./username.txt");
$icons = file("./icon.txt");

MENU:

echo "\n";
Console::log('Bạn đã nhập '.count($friends).' bạn bè, đây là thực đơn hay chọn món 🤭');

echo "\n";
Console::menu('Đi tip thôi nào', 1);
Console::menu('Cập nhật lượng $DEGEN tip cho bạn bè', 2);
Console::menu('Bấm X để thoát nhé', "x");
echo "\n";

do {
    Console::log('Bạn chọn? : ');
    $menu = trim(fgets(STDIN));
    if ($menu == "x" || $menu == "X") {
        Console::log('Tạm biệt - Nếu có vẫn đề gì hãy liên hệ Warpcast: DangDang', 'green');
        die();
    }

    if ($menu == "1" || $menu == "2") {
        break;
    }
} while (true);

echo "\n";

if ($menu == 1) {

    if (empty($config["max"]) || empty($config["min"])) {
        Console::log('Oh no, hãy cập nhật lượng $DEGEN tip mỗi ngày trước nhé', 'red');
        Console::log('Tối đa (Max) : ');
        $max = trim(fgets(STDIN));
        Console::log('Tối thiểu (Min) : ');
        $min = trim(fgets(STDIN));
        if ($min > $max) {
            Console::log('Sai rồi con bò!!, chúng tôi sẽ tự động lấy 1 số cố định là tối đa cho bạn', 'red');
            $min = $max;
        }
        $config["max"] = $max;
        $config["min"] = $min;
        file_put_contents('config.cfg', json_encode($config));
    }

    $count = 0;
    foreach ($friends as $name) {
        $name = trim($name);
        $request->setAddress("https://client.warpcast.com/v2/user-by-username?username=$name");
        $request->setRequestType("GET");
        $request->execute();

        $fid = json_decode($request->getResponse())->result->user->fid;
        $displayName = json_decode($request->getResponse())->result->user->displayName;

        Console::log('═══════ Tip For: '.$displayName.' ═══════', "blue");

        $request->setAddress("https://client.warpcast.com/v2/profile-casts?fid=$fid&limit=1");
        $request->setRequestType("GET");
        $request->execute();

        $cast = json_decode($request->getResponse())->result->casts[0];
        $hash = $cast->hash;
        $time = date("Y-m-d", substr($cast->timestamp, 0, 10));

        if ($time != date("Y-m-d")) {
            Console::log('Hôm nay bạn ấy chưa đăng bài...', "red");
        }

        $nic = rand(0,3);
        $cic = "";
        for ($i=0; $i < $nic; $i++) { 
            $ic = trim($icons[rand(0, count($icons) - 1)]);
            $cic .= $ic;
        }
        $cc = trim($ccs[rand(0, count($ccs) - 1)]);

        $degen = rand((int)$config["min"], (int)$config["max"]);
        $content = $cc . ' '.$degen.' $DEGEN '.$cic;

        $request->setAddress("https://client.warpcast.com/v2/casts");
        $request->setRequestType("POST");
        $request->setPostFields('{"text":"'.$content.'","parent":{"hash":"'.$hash.'"},"embeds":[]}');
        $request->execute();

        $count += ($degen *20);

        $res = json_decode($request->getResponse());

        if (!empty($res->result->cast)) {
            Console::log($content, "green");
        } else {
            Console::log('Cast bị lỗi òi...', "red");
        }

        echo "\n";
        sleep(rand(10,20));
    }
    echo "\n";
    Console::log("═════════════════════════════════════════════", "green");
    Console::log("Tuyệt hôm nay bạn đã tip: $count \$DEGEN", "green");
    die();
}

if ($menu == 2) {
    Console::log('Hãy cập nhật lượng $DEGEN đi tip');
    Console::log('Tối đa (Max) : ');
    $max = trim(fgets(STDIN));
    Console::log('Tối thiểu (Min) : ');
    $min = trim(fgets(STDIN));
    if ($min > $max) {
        Console::log('Sai rồi con bò!!, chúng tôi sẽ tự động lấy 1 số cố định là tối đa cho bạn', 'red');
        $min = $max;
    }
    $config["max"] = $max;
    $config["min"] = $min;
    file_put_contents('config.cfg', json_encode($config));
    goto MENU;
}
