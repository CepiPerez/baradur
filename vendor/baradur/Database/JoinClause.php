<?php

Class JoinClause extends Builder
{

    public $type = null;
    public $table = null;
    //protected $parentConnection;
    //protected $parentGrammar;
    //protected $parentProcessor;
    protected $parentClass=null;

    public function __construct($parentQuery, $type, $table)
    {
        $this->type = $type;
        $this->table = $table;
        $this->parentClass = $parentQuery;
        $this->grammar = $parentQuery->grammar;
        //$this->parentGrammar = $parentQuery->getGrammar();
        //$this->parentProcessor = $parentQuery->getProcessor();
        //$this->parentConnection = $parentQuery->getConnection();

        /* parent::__construct(
            $this->parentConnection, $this->parentGrammar, $this->parentProcessor
        );  */   
    }

    /**
     * Add an "on" clause to the join.
     *
     * On clauses can be chained, e.g.
     *
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     *
     * will produce the following SQL:
     *
     * on `contacts`.`user_id` = `users`.`id` and `contacts`.`info_id` = `info`.`id`
     *
     * @return JoinClause
     */
    public function on($first, $operator, $second = null, $boolean = 'AND')
    {
        /* if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        } */

        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
     *
     * @return JoinClause
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'OR');
    }

}