<?php

namespace App\Plugins\NotifyDiscord;

use Exceedone\Exment\Services\Plugin\PluginEventBase;
use Exceedone\Exment\Model\CustomTable;
use Exceedone\Exment\Enums\ValueType;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Plugin extends PluginEventBase
{
    protected $useCustomOption = true;

    /**
     * Plugin Trigger
     */
    public function execute()
    {
        $PLUGIN_DIR = 'plugins/NotifyDiscord/public/';
        $webhook_url = $this->plugin->getCustomOption('webhook_url');
        $ignore = $this->plugin->getCustomOption('ignore');

        // プラグインフォルダを取得
        $dir_path = $this->plugin->getFullPath();

        // カスタムテーブルの表示名を取得する
        $display_name = '' . $this->custom_table->table_view_name;

        // カスタムテーブルの値のモデルインスタンスを取得する
        $query = $this->custom_table->getValueModel()->query();
        
        // updated_atの降順でソートすることで、最終更新データを取得する
        $query->orderBy('updated_at', 'desc');

        // クエリ結果の最初の行を返す(最新レコード)
        $newest_record = $query->first();
        $output_day = $newest_record->updated_at;
        $values = $newest_record->getValues();
        $output_text = '';
        foreach ($values as $key => $value)
        {
            if($ignore !== $key)
            {
                // $output_text .= $key . ':';
                $val = $newest_record->getValue($key);
                // $output_text .= str_replace(["\r\n", "\r", "\n", "[", "]", "\"" ], '_', $val);
                $output_text .= str_replace(["\"" ], '\"', $val);
                $output_text .= '\n\n';
            }
        }
        $output_text = str_replace(["\r\n", "\r", "\n"], '\n', $output_text);

        // Discordに送信
        $jsonData = '{"content": "<' . $output_day. '> ' . $display_name . '\n```' . $output_text . '```"}';
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false)
        {
            return false;
        }

        return true;

    }

    /**
     * @param [type] $form
     * @return void
     */
    public function setCustomOptionForm(&$form)
    {

        $form->text('webhook_url', 'WebhookURL')
            ->help('Discordの連携サービスで作成したWebhookURLを記入します。');

        $form->text('ignore', '除外カスタム列')
        ->help('除外するカスタム列がある場合は記入します。');
    }
}
