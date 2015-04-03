<?php
/**
 * @brief 기본적인 노드 기능을 제공한다.
 * @author A2
 */
class SimpleNode {
    private $node_name;
    private $node_value;
    private $parent_node;
    private $child_nodes = array();
    private $attributes = array();

    /**
     * @brief 노드를 복사한다.
     * @param $node SimpleNode 복사할 노드
     * @param $deep boolean 자식노드까지 복사 할 것인지 여부
     * @return SimpleNode 복사된 노드
     */
    private static function copy(SimpleNode $node, $deep = false) {
        // 같은 이름의 노드생성
        $copy_node = new self($node->node_name());
        // 값 복사
        $copy_node->set_node_value($node->get_node_value());
        // 속성 복사
        foreach ( $node->attributes as $name => $value ) {
            $copy_node->set_attribute($name, $value);
        }

        // 자식노드까지 복사라면
        if ( $deep ) {
            foreach ( $node->child_nodes() as $child ) {
                $copy_child = self::copy($child, true);
                $copy_node->append_child($copy_child);
            }
        }

        return $copy_node;
    }

    /**
     * @brief 부모노드를 제거한다.
     */
    private function remove_parent() {
        if ( !$this->parent_node ) return;

        $this->parent_node->remove_child($this);
        $this->parent_node = null;
    }

    /**
     * @brief 부모노드를 설정한다.
     * @param $new_parent SimpleNode 부모로 설정할 노드
     */
    private function set_parent(SimpleNode $new_parent) {
        // 기존 부모에게서 떨어져 나온다.
        $this->remove_parent();
        // 새 부모
        $this->parent_node = $new_parent;
    }

    /**
     * @brief 생성자
     * @param $name string 노드이름
     */
    public function __construct($name) {
        $this->node_name = (string)$name;
    }

    /**
     * @brief 노드이름을 반환한다.
     * @return string
     */
    public function node_name() {
        return $this->node_name;
    }

    /**
     * @brief 노드값을 반환한다.
     * @return mixed
     */
    public function get_node_value() {
        return $this->node_value;
    }

    /**
     * @brief 노드값을 설정한다.
     * @param $value mixed 값
     */
    public function set_node_value($value) {
        $this->node_value = $value;
    }

    /**
     * @brief 부모노드를 반환한다.
     * @return SimpleNode
     */
    public function parent_node() {
        return $this->parent_node;
    }

    /**
     * @brief 자식노드 배열을 반환한다.
     * @return array
     */
    public function child_nodes() {
        return $this->child_nodes;
    }

    /**
     * @brief 자식노드를 추가한다.
     * @param $new_child SimpleNode 추가할 노드
     * @return SimpleNode 추가된 노드
     */
    public function append_child(SimpleNode $new_child) {
        if ( $this !== $new_child ) {
            // 자식 추가
            $this->child_nodes[] = $new_child;
            // 부모 설정
            $new_child->set_parent($this);
        }

        return $new_child;
    }

    /**
     * @brief 지정한 자식노드의 앞에 자식노드를 추가한다.
     * @param $new_child SimpleNode 추가할 노드
     * @param $ref_child SimpleNode 위치를 지정할 자식노드
     * @return SimpleNode 추가된 노드
     */
    public function insert_before(SimpleNode $new_child, SimpleNode $ref_child) {
        if ( $this !== $new_child ) {
            $cnt = count($this->child_nodes);
            for ( $i = 0; $i < $cnt; $i++ ) {
                if ( $this->child_nodes[$i] == $ref_child ) {
                    $first = array_slice($this->child_nodes, 0, $i);
                    $first[] = $new_child;
                    $last = array_slice($this->child_nodes, $i);
                    $this->child_nodes = array_merge($first, $last);
                    // 부모 설정
                    $new_child->set_parent($this);
                    break;
                }
            }
        }

        return $new_child;
    }

    /**
     * @brief 자식노드를 제거한다.
     * @param $child SimpleNode 제거할 자식노드
     * @return SimpleNode 제거된 자식노드
     */
    public function remove_child(SimpleNode $child) {
        $cnt = count($this->child_nodes);
        for ( $i = 0; $i < $cnt; $i++ ) {
            if ( $this->child_nodes[$i] == $child ) {
                $first = array_slice($this->child_nodes, 0, $i);
                $last = array_slice($this->child_nodes, $i + 1);
                $this->child_nodes = array_merge($first, $last);
                // 부모 지움
                $child->remove_parent();
                break;
            }
        }

        return $child;
    }

    /**
     * @brief 속성의 값을 반환한다.
     * @param $name string 속성이름
     * @return string
     */
    public function get_attribute($name) {
        return (string)$this->attributes[$name];
    }

    /**
     * @brief 속성의 값을 설정한다.
     * @param $name string 속성이름
     * @param $value string 속성값
     */
    public function set_attribute($name, $value) {
        $name = (string)$name;
        $value = (string)$value;
        $this->attributes[$name] = $value;
    }

    /**
     * @brief 속성을 제거한다.
     * @param $name string 속성이름
     */
    public function remove_attribute($name) {
        unset($this->attributes[$name]);
    }

    /**
     * @brief 속성배열을 반환한다.
     * @return array
     */
    public function attributes() {
        return $this->attributes;
    }

    /**
     * @brief 동일한 노드를 복사해 반환한다.
     * @param $deep boolean 자식노드까지 복사 할 것인지 여부
     * @return SimpleNode 복사된 노드
     */
    public function clone_node($deep = false) {
        return self::copy($this, $deep);
    }
}
?>