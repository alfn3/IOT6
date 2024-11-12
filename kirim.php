<?php

// Memuat class phpMQTT
use Bluerhinos\phpMQTT;
require("vendor/bluerhinos/phpmqtt/phpMQTT.php");

// Mengatur host, port, dan topik MQTT
$host = "broker.hivemq.com";   
$port = 8000;
$topicTemp = "iot/kendali/alfian&agrytemp";
$topicHumd = "iot/kendali/alfian&agryhumd";
$topicsLampu = [
    "iot/kendali/alfian&agrylampu1",
    "iot/kendali/alfian&agrylampu2",
    "iot/kendali/alfian&agrylampu3"
];

// Membuat instance dari phpMQTT
$mqtt = new phpMQTT($host, $port, "PHPClient".rand());

if ($mqtt->connect()) {
    $temp = '';
    $humd = '';

    // Menyusun array topik untuk langganan
    $topics = [
        $topicTemp => ["qos" => 0, "function" => "procMsg"],
        $topicHumd => ["qos" => 0, "function" => "procMsg"]
    ];

    // Menambahkan topik kontrol lampu
    foreach ($topicsLampu as $lampTopic) {
        $topics[$lampTopic] = ["qos" => 0, "function" => "procMsg"];
    }

    // Melakukan subscribe ke topik MQTT
    $mqtt->subscribe($topics, 0);

    // Proses pesan MQTT
    while ($mqtt->proc()) {}

    // Menutup koneksi MQTT
    $mqtt->close();
} else {
    // Mengirimkan status gagal jika tidak bisa terkoneksi ke MQTT
    echo json_encode(["status" => "failed"]);
    exit;
}

// Fungsi untuk memproses pesan MQTT yang diterima
function procMsg($topic, $msg) {
    global $temp, $humd;

    // Jika topik adalah suhu, simpan nilai suhu
    if ($topic === 'iot/kendali/alfian&agrytemp') {
        $temp = $msg;
    } 
    // Jika topik adalah kelembaban, simpan nilai kelembaban
    elseif ($topic === 'iot/kendali/alfian&agryhumd') {
        $humd = $msg;
    } 
    // Jika topik adalah kontrol lampu, tampilkan status lampu
    else {
        $lampId = substr($topic, -1); // Dapatkan ID lampu dari nama topik
        echo "Lampu $lampId status: " . ($msg == "1" ? "On" : "Off") . "\n";
    }

    // Mengirimkan data suhu dan kelembaban sebagai JSON ke web dashboard
    echo json_encode(["temp" => $temp, "humd" => $humd]);
}

// Fungsi untuk mengirim perintah kontrol lampu
if (isset($_GET['lamp']) && isset($_GET['value'])) {
    $lampId = (int)$_GET['lamp'];
    $value = $_GET['value'] === "1" ? "1" : "0";

    // Mengecek ID lampu dan mengirimkan perintah ke MQTT
    if ($lampId >= 1 && $lampId <= 3) {
        $topic = "iot/kendali/alfian&agrylampu$lampId";
        $mqtt->connect();
        // Mengirim perintah ke topik kontrol lampu
        $mqtt->publish($topic, "D$lampId=$value", 0, true);
        $mqtt->close();
    }
}
?>
