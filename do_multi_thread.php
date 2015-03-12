<?php
// 実行
$acc_ids = array(1,2,3,4,5,6,7,8,9,10);
$command = "/summary/update.php {$this->start_date} {$this->end_date}";
do_multi_thread("summary_run", "acc_id", 5, $command, $acc_ids);


/**
 * マルチスレッド実行
 * @param string $lock_file_name ロックファイル名
 * @param string $update_id_name 更新対象idの名称（例）target_id
 * @param string $max_thread 最大スレッド数
 * @param string $command 外部コマンド実行文字列の一部
 * @param array $update_ids 更新対象idの配列
 * @return boolean
 */
function do_multi_thread($lock_file_name, $update_id_name, $max_thread, $command, $update_ids) {

    // 定義
    $lock_path_thread = LOCK . $lock_file_name;

    // 分割実行
    $loop_cnt = 1;
    while ($rest = count($update_ids)) {
        $update_id = array_shift($update_ids);
        if ($update_id === null) break;

        // 実行flg
        $do_flg = 0;

        // 空いたスレッドで実行
        for ($i=1; $i<=$max_thread; $i++) {
            $lock_file_path = $lock_path_thread . $i;
            if (!file_exists($lock_file_path)) {

                // ログ出力
                $rest--;
                $this->log("START {$update_id_name}: {$update_id} REST {$update_id_name} count: {$rest}", $this->log_file_name);

                // コマンド実行
                $command = "/srv/www/app {$command} {$update_id} {$lock_file_path} {$this->log_file_name} > /dev/null &";
                $last_line = system($command, $result);

                // コマンド実行エラー
                if ($last_line === false) {
                    $this->log("Command error {$lock_file_name} {$update_id_name}: {$update_id}", $this->log_file_name);
                }

                $do_flg = 1;

                break;
            }
        }

        // 未実行なのでidを戻す
        if (!$do_flg) $update_ids[] = $update_id;

        // ちょっと待つ・・・
        if ($loop_cnt <= $max_thread) {
            sleep(1);
        } else {
            sleep(5);
        }

        // カウントアップ
        $loop_cnt++;
    }

    return true;
}


public function update() {
    if (!$this->validate()) return false;

    // ロック実行
    if (!isset($this->args[3])) return true;
    $lock_file_path = $this->args[3];
    touch($lock_file_path);

    try {
        // 更新PG実行
    } catch (Exception $e) {
        $this->log($e->getMessage(), $log_file_name);
    }

    // 終了ログ
    $this->log("END buyer_id: {$buyer_id}", $log_file_name);

    // ロック解除
    $this->un_lock($lock_file_path);

    return true;
}
