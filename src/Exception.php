<?php

namespace phantomd\orientdb;

/**
 * Exception represents an exception that is caused by some Orient-related operations.
 *
 * @author Anton Ermolovich <anton.ermolovich@gmail.com>
 * @since 2.0
 */
class Exception extends \yii\base\Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'OrientDB Exception';
    }
}
