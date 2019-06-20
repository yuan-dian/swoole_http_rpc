<?php
/**
 *
 * User: 原点
 * Date: 2019/5/29
 * Email: <467490186@qq.com>
 */

class DingMsg
{
    public function run($data)
    {
        try {
            $access_token = isset($data['param']['access_token']) ? $data['param']['access_token'] : '';
            $message = isset($data['param']['message']) ? $data['param']['message'] : '';
            $at_mobiles = isset($data['param']['atMobiles']) ? $data['param']['atMobiles'] : [];
            if (!$access_token || !$message) return;
            $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=' . $access_token;
            $data_string = [
                'msgtype' => 'text',
                'text' => ['content' => $message],
            ];
            if ($at_mobiles) {
                $data_string['at'] = [
                    'atMobiles' => $at_mobiles,
                    'isAtAll' => false
                ];
            }
            $data_string = json_encode($data_string);

            $res = json_decode($this->request_by_curl($webhook, $data_string), true);
            if ($res['errcode'] == 0) {
                $result = [
                    'code' => 1,
                    'msg' => '成功',
                    'res' => []
                ];
            } else {
                $result = [
                    'code' => 0,
                    'msg' => '失败',
                    'res' => $data
                ];
            }

        } catch (\Exception $error) {
            $result = [
                'code' => 0,
                'msg' => $error->getMessage(),
                'res' => $data
            ];
        }
        return $result;
    }

    /**
     * 发送请求
     * @param $remote_server
     * @param $post_string
     * @return bool|string
     */
    public function request_by_curl($remote_server, $post_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
        // curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}