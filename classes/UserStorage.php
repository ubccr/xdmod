<?php

class UserStorage
{
    const MAX_RECORDS = 2000;

    public function __construct($user, $container)
    {
        $this->_user = $user;
        $this->_container = $container;
    }

    public function get()
    {
        $userProfile = $this->_user->getProfile();
        $storage = $userProfile->fetchValue($this->_container);

        if($storage === null ) {
            return array();
        }

        $output = array();
        foreach($storage['data'] as $value) {
            $output[] = $value;
        }
        return $output;
    }

    public function getById($id)
    {
        $userProfile = $this->_user->getProfile();
        $storage = $userProfile->fetchValue($this->_container);

        if($storage === null ) {
            return null;
        }
        if(!isset($storage['data'][$id])) {
            return null;
        }
        return $storage['data'][$id];
    }

    public function insert(&$data)
    {

        $userProfile = $this->_user->getProfile();
        $storage = $userProfile->fetchValue($this->_container);

        if($storage === null) {
            $storage = array( "maxid" => -1, "data" => array() );
        }

        $newid = $this->_getnewid($storage);
        $data['recordid'] = $newid;

        $storage['data'][ "$newid" ] = $data;

        if( count($storage['data']) > UserStorage::MAX_RECORDS ) {
            return null;
        }
        $userProfile->setValue($this->_container, $storage);
        $userProfile->save();

        return $data;
    }

    private function _getnewid(&$storage)
    {
        $newid = intval(($storage['maxid'] + 1)) % PHP_INT_MAX;
        while(isset($storage['data'][$newid])) {
            $newid = ($newid + 1) % PHP_INT_MAX;
        }
        $storage['maxid'] = $newid;
        return $newid;
    }

    public function upsert($id, $data)
    {
        $userProfile = $this->_user->getProfile();
        $storage = $userProfile->fetchValue($this->_container);

        if($storage === null) {
            $storage = array( "maxid" => $id, "data" => array() );
        }
        $data['recordid'] = $id;

        $storage['data'][ $id ] = $data;
        $storage['maxid'] = max($id, $storage['maxid'] );

        if( count($storage['data']) > UserStorage::MAX_RECORDS ) {
            return null;
        }
        $userProfile->setValue($this->_container, $storage);
        $userProfile->save();

        return $data;
    }

    public function del()
    {
        $userProfile = $this->_user->getProfile();
        $userProfile->dropValue($this->_container);
        $userProfile->save();

        return 0;
    }

    public function delById($id)
    {
        $userProfile = $this->_user->getProfile();
        $storage = $userProfile->fetchValue($this->_container);

        if($storage === null) {
            return 0;
        }
        unset($storage['data']["$id"]);
        $count = count($storage['data']);
        if( $count == 0 ) {
            $storage['maxid'] = -1;
        } else if ( $id == $storage['maxid'] ) {
            $storage['maxid'] = max( array_keys( $storage['data'] ) );
        }

        $userProfile->setValue($this->_container, $storage);
        $userProfile->save();

        return $count;
    }
}
