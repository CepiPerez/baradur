<?php

Class JoinClause extends Builder
{

    public function __construct($table)
    {
        $this->_table = $table;
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
    public function on($first, $operator, $second = null, $boolean = 'and')
    {
        //dd(func_get_args());
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
        return $this->on($first, $operator, $second, 'or');
    }

}