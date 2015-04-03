<?php
/**
 * @brief 노드로 구성된 기본적인 문서 기능을 제공한다.
 * @details HTMLParser 클래스를 이용하여 데이터를 파싱하고 DOM으로 만들어준다.
 * 태그가 열리고 닫힘이 어긋난 경우도 파이어폭스의 결과에 준하게 처리된다.
 * @see HTMLParser
 * @see SimpleNode
 * @author A2
 */
class SimpleDocument extends SimpleNode {
    private $node;

    /**
     * @brief 노드객체로부터 HTML를 만들어 반환한다.
     * @param $node SimpleNode 노드객체
     * @return string
     */
    private static function make_code(SimpleNode $node) {
        if ( $node->node_name() == '#text' ) {
            return $node->get_node_value();
        }

        # 자식코드 #
        $child_code = array();
        foreach ( $node->child_nodes() as $child ) {
            $child_code[] = self::make_code($child);
        }
        $child_code = implode('', $child_code);

        // document는 자식코드를 반환하기만 하면 된다.
        if ( $node->node_name() == '#document' ) {
            return $child_code;
        }

        # 속성코드 #
        if ( $node->attributes() ) {
            $attr_code = array();
            foreach ( $node->attributes() as $name => $value ) {
                $attr_code[] = "{$name}=\"{$value}\"";
            }
            $attr_code = ' '.implode(' ', $attr_code);
        } else {
            $attr_code = '';
        }

        # 전체코드 #
        if ( $child_code ) {
            return "<{$node->node_name()}{$attr_code}>{$child_code}</{$node->node_name()}>";
        } else {
            return "<{$node->node_name()}{$attr_code} />";
        }
    }

    /**
     * @brief 닫히는 태그이름과 짝이 맞지 않는 노드를 교정 한다.
     * @param $node SimpleNode 노드객체
     * @param $tag_name string 닫히는 태그이름
     * @return SimpleNode
     */
    private static function repair_node(SimpleNode $node, $tag_name) {
        if ( $node->node_name() == $tag_name ) return $node;
        if ( !$node->parent_node() ) return $node;

        if ( $node->parent_node()->node_name() == $tag_name ) {
            // 자신을 복사해서
            $clone_node = $node->clone_node();
            // 부모의 부모의 자식으로 추가
            $node->parent_node()->parent_node()->append_child($clone_node);
        } else {
            // 부모 교정
            $parent_node = self::repair_node($node->parent_node(), $tag_name);
            // 원래 부모와 동일하면 그대로 반환
            if ( $parent_node === $node->parent_node() ) {
                return $node;
            }

            // 자신을 복사해서
            $clone_node = $node->clone_node();
            // 새 부모노드의 자식으로 추가
            $parent_node->append_child($clone_node);
        }

        return $clone_node;
    }

    /**
     * @brief 파서의 close_element 핸들러
     * @param $tag_name string 태그이름
     * @param $attr_list 속성배열
     */
    public function start_element($tag_name, $attr_list) {
        // 새 노드 생성
        $node = new SimpleNode($tag_name);
        // 속성 추가
        foreach ( $attr_list as $name => $value ) {
            $node->set_attribute($name, $value);
        }

        // 현재노드의 자식노드로 들어간다.
        $this->node->append_child($node);
        // 현재노드 교체
        $this->node = $node;
    }

    /**
     * @brief 파서의 start_element 핸들러
     * @param $tag_name string 태그이름
     */
    public function end_element($tag_name) {
        if ( $this->node->node_name() == $tag_name ) {
            // 현재노드를 자신의 부모로 교체
            $this->node = $this->node->parent_node();
        } else {
            // 현재노드와 종료 태그이름이 다르면 노드를 교정한다.
            $this->node = self::repair_node($this->node, $tag_name);
        }
    }

    /**
     * @brief 파서의 character_data 핸들러
     * @param $data string 문자데이터
     */
    public function character_data($data) {
        // 새 노드 생성
        $node = new SimpleNode('#text');
        $node->set_node_value($data);

        // 현재노드의 자식노드로 들어간다.
        $this->node->append_child($node);
    }

    /**
     * @brief 생성자
     * @param $data string HTML 코드
     */
    public function __construct($data) {
        parent::__construct('#document');

        // 현재노드의 시작은 자신
        $this->node = $this;

        // 파서 생성
        $parser = new HTMLParser();
        $parser->set_start_element_handler(array($this, 'start_element'));
        $parser->set_end_element_handler(array($this, 'end_element'));
        $parser->set_character_data_handler(array($this, 'character_data'));
        // 파싱
        $parser->parse($data);
    }

    /**
     * @brief HTML 코드를 반환한다.
     * @return string
     */
    public function html() {
        return self::make_code($this);
    }
}
?>