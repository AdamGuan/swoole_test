<?php
// 建立客户端的socet连接
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$connection = socket_connect($socket, '121.199.29.107', 9501);    //连接服务器端socket

while ($buffer = @socket_read($socket, 1024, PHP_NORMAL_READ)) {
	//服务端告诉客户端，自己的状态
	if (preg_match("/not connect/",$buffer)) {
		echo "don`t connect\r\n";
		break;
	} else {
		//服务器传来信息
		if(substr($buffer,strlen($buffer)-1,1) == "\n")
		echo "receive first Data: " . substr($buffer,0,-1) . "\r\n";

		//echo "Writing to Socket.\r\n";
		// 将客户的信息写到通道中，传给服务器端
		//if (!socket_write($socket, "SOME DATA\n")) {
		//	echo "Write failed\r\n";
		//}
		//服务器端收到信息后，给于的回应信息
		while ($buffer = socket_read($socket, 1024, PHP_NORMAL_READ)) {
				//if(substr($buffer,strlen($buffer)-1,1) == "\n")
				echo "receive Data:" . substr($buffer,0,-1) . ".\r\n";
		}
		echo "end!";

	}
}

?>