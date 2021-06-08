<?php

require_once("./src/utils/utils.php");

class PaymentSlip {
    protected $payment_code_bar;
    protected $payment_template_local = "./src/assets/boleto.html"; //"../assets/boleto.html";

    protected $bank_code;
    protected $bank_name; 
    protected $agency; 
    protected $account; 
    protected $wallet; 
    protected $client_number; 
    protected $client_name; 
    protected $client_code; 
    protected $value; 
    protected $due_date;
    protected $document_number;
    protected $payment_place; 
    protected $payment_instructions;

    const CURRENCY_CODE = 9;

    function __construct($obj_data_payment)
    {
        $this->bank_code            = $obj_data_payment->codigoBanco; 
        $this->bank_name            = $obj_data_payment->nomeBanco;
        $this->agency               = get_only_digits($obj_data_payment->beneficiario->agencia); 
        $this->account              = get_only_digits($obj_data_payment->beneficiario->conta); 
        $this->wallet               = $obj_data_payment->beneficiario->carteira; 
        $this->client_code          = get_only_digits($obj_data_payment->beneficiario->cnpj); 
        $this->client_name          = $obj_data_payment->beneficiario->nome;  
        $this->value                = get_only_digits( $obj_data_payment->pagador->valor); 
        $this->due_date             = $obj_data_payment->pagador->vencimento;
        $this->payment_place        = $obj_data_payment->localPagamento; 
        $this->payment_instructions = $obj_data_payment->instrucoes;
        $this->document_number      = $obj_data_payment->pagador->numero_doc;
    }

    public function get_11_module($str_number) {
        $arr_int_m11_seq = array(2,3,4,5,6,7,8,9);
        $int_index_seq = 0;

        $int_sum = 0;
        $int_aux_var1 = 0;

        for ($int_i = strlen($str_number)-1; $int_i >=0; $int_i--) {
            $int_aux_var1 = (int)$str_number[$int_i] * $arr_int_m11_seq[$int_index_seq];
            $int_sum =  $int_sum +  $int_aux_var1;

            $int_index_seq++;

            if ( $int_index_seq > 7) {  $int_index_seq = 0; }
        }

        $int_modulus =   $int_sum % 11;

        $int_dac = 11 - $int_modulus;

        if (( $int_dac < 2) || ( $int_dac > 9)) {
            return "1";
        }

        return (string)$int_dac;

    }

    public function get_10_module($str_number) {
        $bol_aux_start_with_2 = true;

        $int_aux_var1 = 0;
        $str_temp_var1 = "";

        for ($int_i = strlen($str_number)-1; $int_i >=0 ;$int_i--) {
            
            $int_aux_var1 = ($bol_aux_start_with_2) ? (int)$str_number[$int_i] * 2 : (int)$str_number[$int_i];
            $str_temp_var1 .= (string)$int_aux_var1;
            
            $bol_aux_start_with_2 = !$bol_aux_start_with_2;

        }

        $int_sum = 0;

        for ($int_i =0; $int_i < strlen($str_temp_var1); $int_i++) {
            $int_sum += (int)$str_temp_var1[$int_i];
        }

        $int_modulus =   $int_sum % 10;

        $int_dac = 10 - $int_modulus;

        if ( $int_dac == 10) {
            return "0";
        }

        return (string)$int_dac;

    }

    //Format Date: 'YYYY-MM-DD'. Formato da data: 'AAAA-MM-DD'
    public function get_due_date_factor($str_due_data) {
        $obj_due_date = date_create($str_due_data);
        $obj_base_date = date_create("1997-10-07");
        
        $obj_days_diif = date_diff($obj_base_date, $obj_due_date);

        return get_only_digits($obj_days_diif->format("%a"), 4);
    }

    public function create_payment_code_bar($str_free_field) {
        $str_due_factor = $this->get_due_date_factor(  $this->due_date );
        $str_value_len10 = get_only_digits($this->value, 10);

        $str_temp_code = $this->bank_code . self::CURRENCY_CODE .  $str_due_factor . $str_value_len10 . $str_free_field;  
        
        $str_code_bar_dac = $this->get_11_module($str_temp_code);

        $payment_code_bar = $this->bank_code . self::CURRENCY_CODE . $str_code_bar_dac . $str_due_factor . $str_value_len10 . $str_free_field;

        $this->payment_code_bar = $payment_code_bar;
        //return $this->bank_code . self::CURRENCY_CODE . $str_code_bar_dac . $str_due_factor . $str_value_len10 . $str_free_field;
    }

    public function create_graphic_represetantion_code_bar() {
        $str_start = "SSSS";
        $str_end   = "BSS";

        $arr_digits_cbar = array("SSBBS","BSSSB","SBSSB","BBSSS","SSBSB","BSBSS","SBBSS","SSSBB","BSSBS","SBSBS");

        $int_a1 = 0;
        $int_b1 = 0;

        $str_bar_code_number = $this->payment_code_bar;
        $str_bar_code_number_seq = "";

        while($int_a1 < strlen($str_bar_code_number)) {
            $int_b1= $int_a1+1;
            
            $str_d1 = substr($str_bar_code_number,$int_a1,1);
            $str_d2 = substr($str_bar_code_number,$int_b1,1);
            
            $int_d1 = (int) $str_d1;
            $int_d2 = (int) $str_d2;
            
            for($int_b1 = 0; $int_b1 < 5; $int_b1++) {
                $str_bar_code_number_seq .= substr($arr_digits_cbar[$int_d1],$int_b1,1) . substr($arr_digits_cbar[$int_d2],$int_b1,1);
            }

            $int_a1 = $int_a1 + 2;
        }

        $str_bar_code_number_seq = $str_start . $str_bar_code_number_seq . $str_end;

        $str_html_code = "<img ";
        $bol_black_bar = true;

        for($int_a1=0;$int_a1<strlen($str_bar_code_number_seq);$int_a1++) {

            $str_html_code .= ($bol_black_bar) ? "src=\"p.gif\" width=\"" : "src=\"b.gif\" width=\"";
            
            $bol_black_bar = !$bol_black_bar;

            $str_ind_width = $str_bar_code_number_seq[$int_a1];
            
            if ($str_ind_width === "S") {
                $str_html_code .= "1\" height=\"50\" border=\"0\" /><img \r\n";
            } else {
                $str_html_code .= "3\" height=\"50\" border=\"0\" /><img \r\n";
            }
        }

        $str_html_code = substr($str_html_code,0,strlen($str_html_code)-7); //REMOVENDO FINAL <img \r\n
	    return $str_html_code;
    }

    public function create_code_bar_line() {
        $arr_fields = array("", "", "", "", "");

        $arr_fields[0] = substr($this->payment_code_bar, 0, 4) . substr($this->payment_code_bar, 19, 5);
        $arr_fields[1] = substr($this->payment_code_bar,24,10);
        $arr_fields[2] = substr($this->payment_code_bar,34,10);
        $arr_fields[3] = $this->payment_code_bar[4];
        $arr_fields[4] = substr($this->payment_code_bar, 5, 4) . substr($this->payment_code_bar, 9, 10);

        $arr_fields[0] .= $this->get_10_module($arr_fields[0]);
        $arr_fields[1] .= $this->get_10_module($arr_fields[1]);
        $arr_fields[2] .= $this->get_10_module($arr_fields[2]);

        $str_bar_code_line_ipte = substr($arr_fields[0],0,5) . "." . substr($arr_fields[0],5) . " ";
	    $str_bar_code_line_ipte .= substr($arr_fields[1],0,5) . "." . substr($arr_fields[1],5) . " ";
	    $str_bar_code_line_ipte .= substr($arr_fields[2],0,5) . "." . substr($arr_fields[2],5) . " ";
	    $str_bar_code_line_ipte .= $arr_fields[3] . " " . $arr_fields[4];
	
        return $str_bar_code_line_ipte;
       
    }

}

?>