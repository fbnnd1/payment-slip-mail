<?php

//require("./src/utils/utils.php");
require("./src/interface/interfaces.php");
require("PaymentSlip.php");

class ItauBankPaymentSlip extends PaymentSlip implements InterfaceBank {

    private $free_field;
    private $receipt;
    private $client_lead_name;
    private $client_lead_code;
    private $processing_date;
    private $accountNoChanged;

    function __construct($obj_data_payment, $processing_date) {
        parent::__construct($obj_data_payment);
        $this->receipt = $obj_data_payment->pagador->recibo;
        $this->client_lead_name = $obj_data_payment->pagador->nome;
        $this->client_lead_code = $obj_data_payment->pagador->cpf_cnpj;
        $this->processing_date = $processing_date;
        $this->accountNoChanged = $obj_data_payment->beneficiario->conta;

        $this->get_field_bank();
        $this->create_payment_code_bar($this->free_field);
    }

    public function get_field_bank() {
        $str_wallet_len3         = get_only_digits($this->wallet, 3);
        $str_agency_len4         = get_only_digits($this->agency, 4);
        $str_account_len5        = substr( get_only_digits($this->account), 0, 5);
        $str_receipt_number_len8 = get_only_digits($this->receipt, 8);

        $str_client_number_control_dac = $this->get_10_module($str_agency_len4 . $str_account_len5 .  $str_wallet_len3 . $str_receipt_number_len8);
        
        $str_agency_account_dac = $this->get_10_module($str_agency_len4 . $str_account_len5 );
        
        $this->free_field = $str_wallet_len3 . $str_receipt_number_len8 . $str_client_number_control_dac .  $str_agency_len4 . $str_account_len5 . $str_agency_account_dac . "000";
    }

    public function create_view($output = false) {
        $obj_file_template = fopen($this->payment_template_local, "rt");
        $str_html_template = fread($obj_file_template, filesize($this->payment_template_local));
        fclose($obj_file_template);

        $str_bank_code_formatted = $this->bank_code . "-" .  $this->get_11_module($this->bank_code);

        $str_html_template = str_replace("#banco#",$this->bank_name, $str_html_template);
        $str_html_template = str_replace("#logo#",$this->bank_code . ".jpg", $str_html_template);
        $str_html_template = str_replace("#codigobanco#", $str_bank_code_formatted, $str_html_template);
        $str_html_template = str_replace("#CLIENTE#",$this->client_name, $str_html_template);
        $str_html_template = str_replace("#CNPJ#",$this->client_code, $str_html_template);
        $str_html_template = str_replace("#DATA.VENCIMENTO#",format_date($this->due_date), $str_html_template);
        $str_html_template = str_replace("#N.N#",$this->wallet . "/" . substr($this->payment_code_bar, 22, 8) . "-" .  substr($this->payment_code_bar, 30, 1), $str_html_template);
        $str_html_template = str_replace("#N.DOC#",$this->document_number, $str_html_template);
        $str_html_template = str_replace("#DATA.PROC#",format_date($this->processing_date), $str_html_template);
        $str_html_template = str_replace("#AGENCIA#",$this->agency, $str_html_template);
        $str_html_template = str_replace("#CONTA#", $this->accountNoChanged, $str_html_template);
        $str_html_template = str_replace("#VALOR#", format_value($this->value), $str_html_template);
        $str_html_template = str_replace("#SACADO#",$this->client_lead_name . " cpf/cnpj: " . $this->client_lead_code, $str_html_template);
        $str_html_template = str_replace("#CARTEIRA#",$this->wallet, $str_html_template);
        $str_html_template = str_replace("#LOCAL-PAGTO#",$this->payment_place, $str_html_template);
        $str_html_template = str_replace("#INSTRUCOES#", str_replace("\n", "<br />\n", $this->payment_instructions), $str_html_template);
        $str_html_template = str_replace("#LINHA.DIGITAVEL#", $this->create_code_bar_line() , $str_html_template);
        $str_html_template = str_replace("#REPR.CODBARRAS#", $this->create_graphic_represetantion_code_bar() , $str_html_template);

        if ($output) {
            $str_html_structure_default = "
            <!DOCTYPE html>
            <html lang=\"pt-br\">
            <head>
                <meta charset=\"UTF-8\">
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                <title>Boleto</title>
                <base href=\"../src/assets/\" />
            </head>
            <body>
            #BOLETO#
            </body>
            </html>
            ";

            $str_html_template = str_replace("#BOLETO#", $str_html_template, $str_html_structure_default);
            //$obj_file = fopen("../../output/boleto.html", "wt");
            $obj_file = fopen("./output/boleto.html", "wt");
            fwrite($obj_file, $str_html_template);
            fclose($obj_file);

            return "";
         }

         return $str_html_template;
    }
}

?>