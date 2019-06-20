<?php
/**
 *
 * User: 原点
 * Date: 2019/6/14
 * Email: <467490186@qq.com>
 */

class Server
{
    private $serv;

    public function __construct() {
        $this->serv =   new Swoole\Http\Server("0.0.0.0", 9601);
        $this->serv->set([
            'worker_num'      => 2, //开启2个worker进程
            'task_worker_num' => 8, //开启2个tsak_worker进程
            'max_request'     => 100, //每个worker进程 max_request设置为4次
            'daemonize'       => false, //守护进程(true/false)
        ]);
        $this->serv->on('Start', [$this, 'onStart']);
        $this->serv->on('WorkerStart', [$this, 'onWorkStart']);
        $this->serv->on("Request", [$this, 'onRequest']);
        $this->serv->on('Task', array($this, 'onTask'));
        $this->serv->on("Finish", array($this, "onFinish"));
        $this->serv->start();

    }

    public function onStart($serv) {
        echo "#### onStart ####".PHP_EOL;
        echo "SWOOLE ".SWOOLE_VERSION . " 服务已启动".PHP_EOL;
        echo "master_pid: {$serv->master_pid}".PHP_EOL;
        echo "manager_pid: {$serv->manager_pid}".PHP_EOL;
        echo "########".PHP_EOL.PHP_EOL;
    }

    public function onWorkStart($serv, $worker_id) {
        echo "#### onWorkStart ####".PHP_EOL.PHP_EOL;
        //自动注册类库
        spl_autoload_register(function ($className) {
            $classPath = __DIR__ . "/controller/" . $className . ".php";
            if (is_file($classPath)) {
                require "{$classPath}";
                return;
            }
        });

    }

    public function onRequest($request, $response) {
        try {
            if (empty($request->post) && $request->header['content-type'] == 'application/json') {
                $request->post = json_decode($request->rawContent(),true);
            }
            $controller = isset($request->post['controller']) ? $request->post['controller'] : '';
            $method = isset($request->post['method']) ? $request->post['method'] : '';
            $type = isset($request->post['type']) ? $request->post['type'] : 'SN';
            $param = isset($request->post['param']) ? $request->post['param'] : [];
            $data = [
                'controller' => $controller,
                'method' => $method,
                'type' => $type,
                'param' => $param,
            ];
            $res = [
                'code' => '-1',
                'msg' => 'class 不存在'
            ];
            if (class_exists($controller)) {
                $res = [
                    'code' => '-1',
                    'msg' => 'method 不存在'
                ];
                if (method_exists($controller, $method)) {
                    switch ($type) {
                        case 'SW': //同步请求，使用task模拟同步，防止同步阻塞进程
                            $rs = $this->serv->task(json_encode($data), -1, function ($serv, $task_id, $rs_data) use ($request, $response) {
                                if(!is_string($rs_data)) $rs_data = json_encode($rs_data);
                                return $response->end($rs_data);
                            });
                            if ($rs === false) {
                                $res = [
                                    'code' => '-1',
                                    'msg' => '失败'
                                ];
                            }else {
                                return ;
                            }
                            break;
                        case 'SN': //异步请求
                            $rs = $this->serv->task(json_encode($data));
                            if ($rs === false) {
                                $res = [
                                    'code' => '-1',
                                    'msg' => '失败'
                                ];
                            } else {
                                $res = [
                                    'code' => '1',
                                    'msg' => '成功'
                                ];
                            }
                            break;
                        default:
                            $res = [
                                'code' => '-1',
                                'msg' => '失败'
                            ];
                    }
                }
            }
        }catch (\Exception $error){
            $res = [
                'code' => '-1',
                'msg' => $error->getMessage()
            ];
        }
        return  $response->end(json_encode($res));
    }

    /**
     * task进程
     * @param $serv
     * @param $task_id
     * @param $from_id
     * @param $data
     * @return bool
     */
    public function onTask($serv,$task_id,$from_id, $data) {
        $data = json_decode($data, true);
        $controller = $data['controller'];
        $method = $data['method'];
        $result = false;
        if (class_exists($controller)) {
            $class = new $controller();
            if (method_exists($controller, $method)) {
                $result = $class->$method($data);
            }
        }
        return $result;
    }

    public function onFinish( swoole_server $serv,  $task_id,  $data){
        echo "onFinish\n";
    }

}

$server = new Server();