<?php
$swoole_tmp_file = "/tmp/swoole.tmp";
unlink($swoole_tmp_file);
$test = array(1,2);
$lock = new swoole_lock(SWOOLE_MUTEX);

function my_onStart($serv)
{
	cli_set_process_title("swoole server main");
	echo "Server:start\n";
}

function my_onClose($serv,$fd,$from_id)
{
	echo "Server:close\n";
}

function my_onManagerStart($serv)
{
	cli_set_process_title("swoole server manager");
}

function my_onReceive($serv,$fd,$from_id,$data)
{
	echo "Server:receive data: ".$data;
	/*
	$fdlist = $serv->connection_list();
	if($fdlist !== false)
	{
		if(isset($fdlist[1]))
		{
			echo $serv->send($fdlist[1],$data);
		}
	}
	 */
	/*
	if(isset($fdList[1]))
	{
		echo $serv->send($fdList[1],$data);
	}
	 */
	++$i;
	$serv->send($fd,"i'm good!".$i."\n",$from_id);
}

function my_onConnect($serv,$fd,$from_id)
{
	global $fdList;
	$fdList[] = $fd;
	$serv->send($fd,"wellcom!\n",$from_id);
	/*
	$fdlist = $serv->connection_list();
	if(!isset($fdlist[1]))
	{
		$serv->task("some data");
	}
	 */
}

function my_onWorkerStart($serv,$worker_id)
{
	if($worker_id < $serv->setting['worker_num'])
	{
		cli_set_process_title("swoole server worker");
		echo "woker start!\n";
	}
	else
	{
		global $lock;
		global $swoole_tmp_file;
		$lock->lock();
		$flag = 1000000;
		if(file_exists($swoole_tmp_file))
		{
			$flag = file_get_contents($swoole_tmp_file);
		}

		if(!swoole_process::kill($flag, 0))
		{
			file_put_contents($swoole_tmp_file,$serv->worker_pid);
			cli_set_process_title("swoole server task worker special");
			$serv->task("1");
		}
		else
		{
			cli_set_process_title("swoole server task worker");
		}
		$lock->unlock();
	}
}

function my_onTask($serv,$task_id,$from_id,$data)
{
	if($data == "1")
	{
		echo "task worker pid: ".$serv->worker_pid."\n";
		global $test;
		while(1)
		{
			$fdlist = $serv->connection_list();
			$data = implode(",",$fdlist)."\n";
			if($fdlist !== false)
			{
				foreach($fdlist as $fd)
				{
					$serv->send($fd,$data);
					echo count($test)."\n";
					$test[] = 1;
				}
			}
			sleep(5);
		}
	}
}


function my_onWorkerError($serv,$worker_id,$worker_pid,$exit_code)
{
	echo "workter died: ".$worker_pid."\n";
	global $lock;
	$lock->unlock();
}

function my_onWorkerStop($serv,$worker_id)
{
	echo "workter close\n";
	global $lock;
	$lock->unlock();
}

function my_onFinish($serv,$worker_id)
{
	echo "workter close\n";
	global $lock;
	$lock->unlock();
}

$serv = new swoole_server("0.0.0.0", 9501);
$serv->set(array(
	'timeout' => 2.5,  //select and epoll_wait timeout. 
	'worker_num' => 4,    //worker process num
	'backlog' => 128,   //listen backlog
	'max_request' => 1000,
	'dispatch_mode'=>1, 
	'log_file' => '/alidata/log/swolle/swoole.log',
	'open_eof_check'=>true,
	'package_eof'=>"\n",
	'task_worker_num'=>10,
	//'heartbeat_check_interval'=>5,
	//'heartbeat_idle_time'=>10,
	'open_tcp_keepalive'=>1,
	'tcp_keepidle'=>5,
	'tcp_keepcount'=>3,
	'tcp_keepinterval'=>3,
));
$serv->handler('onStart', 'my_onStart');
$serv->handler('onClose', 'my_onClose');
$serv->handler('onReceive', 'my_onReceive');
$serv->handler('onConnect', 'my_onConnect');
$serv->handler('onWorkerStart', 'my_onWorkerStart');
$serv->handler('onTask', 'my_onTask');
$serv->handler('onFinish', 'my_onFinish');
$serv->handler('onManagerStart', 'my_onManagerStart');
$serv->handler('onWorkerError', 'my_onWorkerError');
$serv->handler('onWorkerStop', 'my_onWorkerStop');
$serv->handler('onFinish', 'my_onFinish');
$serv->start();
