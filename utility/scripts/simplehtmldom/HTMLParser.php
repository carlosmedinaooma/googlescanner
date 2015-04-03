<?php
/**
 * @brief HTML 파서
 * @details 문법에 어긋나는 HTML 문자열을 파싱할 수 있다.
 * 어긋난 문법에 대한 처리방식은 파이어폭스를 참고하여 조정하였다.
 * @author A2
 */
class HTMLParser {
    private $data;
    private $offset = 0; // 처리중인 데이터 위치
    private $commit_offset = 0; // 처리되어 넘어간 위치
    private $start_element_handler;
    private $end_element_handler;
    private $character_data_handler;

    /**
     * @brief HTML의 완료태그인지 여부 반환
     * @param $tag_name string 태그이름
     * @return boolean
     */
    private static function is_html_complete_tag($tag_name) {
        static $regexp = '/^(?:!doctype|area|br|embed|hr|iframe|img|link|meta|param)$/';
        return preg_match($regexp, $tag_name) === 1;
    }

    /**
     * @brief 태그 시작 콜백호출
     * @param $tag_name string 태그이름
     * @param $attr_list array 속성배열
     */
    private function callback_start_element($tag_name, $attr_list) {
        if ( !$this->start_element_handler ) return;
        call_user_func_array($this->start_element_handler, array($tag_name, $attr_list));
    }

    /**
     * @brief 태그 종료 콜백호출
     * @param $tag_name string 태그이름
     */
    private function callback_end_element($tag_name) {
        if ( !$this->end_element_handler ) return;
        call_user_func_array($this->end_element_handler, array($tag_name));
    }

    /**
     * @brief 문자데이터 콜백호출
     * @param $character_data string 문자데이터
     */
    private function callback_character_data($character_data) {
        if ( !$this->character_data_handler ) return;
        call_user_func_array($this->character_data_handler, array($character_data));
    }

    /**
     * @brief 태그 시작 핸들러 설정
     * @param $callback callback 콜백
     */
    public function set_start_element_handler($callback) {
        $this->start_element_handler = $callback;
    }

    /**
     * @brief 태그 종료 핸들러 설정
     * @param $callback callback 콜백
     */
    public function set_end_element_handler($callback) {
        $this->end_element_handler = $callback;
    }

    /**
     * @brief 문자데이터 핸들러 설정
     * @param $callback callback 콜백
     */
    public function set_character_data_handler($callback) {
        $this->character_data_handler = $callback;
    }

    /**
     * @brief HTML 문자열을 파싱한다.
     * @param $data string HTML 문자열
     */
    public function parse($data) {
        $this->data = (string)$data;
        $script_start = false;

        while ( true ) {
            ## 태그 찾기 ##
            $regexp = '#<(?:/|!--)?([^<>\s/]+)#';
            $result = preg_match($regexp, $this->data, $match, PREG_OFFSET_CAPTURE, $this->offset);
            if ( !$result ) break;

            // 문자데이터 끝 위치
            $character_pos = $match[0][1];
            // 태그이름 시작 위치
            $tag_name_pos = $match[1][1];
            // 태그 이름
            $tag_name = strtolower($match[1][0]);
            // 태그 타입 구분
            if ( substr($match[0][0], 1, 1) == '/' ) {
                $tag_type = 'close';
            } else {
                $tag_type = 'open';
            }

            // 데이터 처리 위치는 태그명 이후
            $this->offset = $tag_name_pos + strlen($tag_name);

            // script 태그가 시작된 상태라면
            if ( $script_start ) {
                // 반드시 닫힌 script 태그가 짝을 이뤄야 한다.
                if ( $tag_type != 'close' || $tag_name != 'script' ) {
                    continue;
                }
            }

            // 주석 태그 처리
            if ( strpos($tag_name, '!--') === 0 ) {
                // 문자데이터가 있다면
                if ( $character_pos != $this->commit_offset ) {
                    $len = $character_pos - $this->commit_offset;
                    $character_data = substr($this->data, $this->commit_offset, $len);
                    $this->callback_character_data($character_data);
                }

                // 주석 종료 부분을 찾는다.
                $pos = strpos($this->data, '-->', $this->offset);
                $len = 3;
                if ( $pos === false ) {
                    $pos = strpos($this->data, '>', $this->offset);
                    $len = 1;
                    if ( $pos === false ) {
                        break;
                    }
                }

                // 데이터 위치 이동
                $this->offset = $pos + $len;
                $this->commit_offset = $this->offset;
                continue;
            }

            ## 태그 파싱 ##
            $attr_list = array();
            $current_attr = null;
            while ( true ) {
                if ( !$current_attr ) {
                    // <, >, 이름
                    $regexp = '#<|>|[^\s\'"<>=/]+#';
                } else {
                    // =, <, >, 이름
                    $regexp = '#=|<|>|[^\s\'"<>=/]+#';
                }

                $result = preg_match($regexp, $this->data, $match, PREG_OFFSET_CAPTURE, $this->offset);
                if ( !$result ) break;

                $str = $match[0][0];
                $pos = $match[0][1];

                ## 태그가 완료된 상황 ##
                if ( $str == '<' ) { // 다른 태그 시작
                    $this->offset = $pos;
                    break;
                } else if ( $str == '>' ) { // 태그 종료
                    // 단일태그 여부
                    if ( substr($this->data, $pos - 1, 1) == '/' ) {
                        $tag_type = 'complete';
                    }

                    $this->offset = $pos + 1;
                    break;
                }

                // 데이터 위치 이동
                $this->offset = $pos + strlen($str);

                // 현재속성이 없으면
                if ( !$current_attr ) {
                    $current_attr = strtolower($str);
                    $attr_list[$current_attr] = '';
                    continue;
                }

                // '='는 현재속성이 있을 경우만 나타날 수 있다.
                if ( $str == '=' ) {
                    $regexp = '/\'|"|[^\s\>]+/';
                    $result = preg_match($regexp, $this->data, $match, PREG_OFFSET_CAPTURE, $this->offset);
                    if ( !$result ) break;

                    $str = $match[0][0];
                    $pos = $match[0][1];

                    if ( $str == "'" || $str == '"' ) {
                        // 다음 따옴표 찾기
                        $pos_close = strpos($this->data, $str, $pos + 1);
                        if ( $pos_close !== false ) {
                            // 따옴표안의 값이 속성값
                            $attr_value = substr($this->data, $pos + 1, $pos_close - $pos - 1);
                            $this->offset = $pos_close + 1;
                        } else {
                            // 따옴표에 붙어서 공백없이 이어진 문자열을 찾는다. (O)alt="value
                            $regexp = '/[^\s\>]+/';
                            $result = preg_match($regexp, $this->data, $match, PREG_OFFSET_CAPTURE, $pos + 1);
                            if ( !$result ) break;
                            // 찾은 문자열은 따옴표와 붙어있지 않으면 안된다. (X)alt=" value
                            if ( $pos + 1 != $match[0][1] ) break;
                            // 찾은 문자열이 속성값
                            $attr_value = $match[0][0];
                            $this->offset = $match[0][1] + strlen($attr_value);
                        }
                    } else {
                        $attr_value = $str;
                        $this->offset = $pos + strlen($str);
                    }

                    // 현재속성에 값 부여
                    $attr_list[$current_attr] = $attr_value;
                    // 현재속성 초기화
                    $current_attr = null;
                } else {
                    // 새로운 현재속성
                    $current_attr = $str;
                    $attr_list[$current_attr] = '';
                }
            } // 속성 파싱 끝


            ## 위의 파싱 결과에 따른 처리 ##
            // HTML 완료태그 확인
            $html_complete_tag = self::is_html_complete_tag($tag_name);
            if ( $html_complete_tag ) {
                if ( $tag_type == 'open' ) {
                    $tag_type = 'complete';
                } else if ( $tag_type == 'close' ) {
                    $tag_type = 'nil';
                }
            } else {
                if ( $tag_type == 'complete' ) {
                    $tag_type = 'open';
                }
            }

            // script 태그일때
            if ( $tag_name == 'script' ) {
                if ( $script_start && $tag_type == 'close' ) {
                    // script가 시작되어 있는 상태일때 종료 script 태그
                    $script_start = false;
                } else if ( !$script_start && $tag_type == 'open' ) {
                    // script가 시작하지 않은 상태일때 시작 script 태그
                    $script_start = true;
                }
            }

            // 문자데이터가 있다면
            if ( $character_pos != $this->commit_offset ) {
                $len = $character_pos - $this->commit_offset;
                $character_data = substr($this->data, $this->commit_offset, $len);
                $this->callback_character_data($character_data);
            }

            // 타입에 따른 콜백
            switch ( $tag_type ) {
                case 'open':
                    $this->callback_start_element($tag_name, $attr_list);
                    break;
                case 'close':
                    $this->callback_end_element($tag_name);
                    break;
                case 'complete':
                    $this->callback_start_element($tag_name, $attr_list);
                    $this->callback_end_element($tag_name);
                    break;
            }

            // 처리가 완료된 데이터 위치를 기억한다.
            $this->commit_offset = $this->offset;
        } // end while

        // 처리되지 않고 남은 데이터는 문자데이터 콜백처리
        $character_data = substr($this->data, $this->commit_offset);
        if ( $character_data !== false ) {
            $this->callback_character_data($character_data);
        }
        $this->data = null;
    }
}
?>