<?php

namespace App\Http\Controllers;

use DB;
use App\UpTUser;

class UpdateSearchWordController extends Controller
{

    public function update()
    {
        try {
            $users     = UpTUser::where('search_word', '')->get()->toArray();
            $ids       = '';
            $openSql   = 'UPDATE user 
                          SET `search_word` =
                          CASE
                              id ';
            $sqlString = $openSql;
            $numberUsers = count($users);

            foreach ($users as $key => $user) {
                $searchWord = addslashes(implode('', $user));

                if (empty($ids)) {
                    $ids = sprintf("'%s'", $user['id']);
                } else {
                    $ids .= sprintf(" , '%s'", $user['id']);
                }

                $sqlString .= sprintf(" WHEN '%s' THEN '%s' ", $user['id'], $searchWord);
                if ($key != 0 && ($key + 1) % 1000 == 0) {
                    $sqlString .= sprintf(" END 
                        WHERE
                            id IN (%s); %s", $ids, $key + 1 < $numberUsers ? $openSql : '');
                    $ids       = '';
                }

            }

            if ($numberUsers > 0 && $numberUsers % 1000 != 0) {
                $sqlString .= sprintf(" END 
                        WHERE
                            id IN (%s);", $ids);
            }

            var_dump($sqlString);
            DB::beginTransaction();

            DB::commit();
            dd('done');
        } catch (\Exception $exception) {
            DB::rollBack();
            var_dump("something went wrong");
            dd($exception);
        }
    }
}
