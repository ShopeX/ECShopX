<?php

use Illuminate\Database\Seeder;
//use App\Entities\Post;
use PostBundle\Entities\Post;

class PostTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $p1 = new Post;
        $p1->setUserId(33);
        $p1->setContent('content');
        $p1->setTitle('title');
        $p1->setExtra('extra');
        //$conn = app('registry')->getConnection('default');
        $em = app('registry')->getManager('default');
        $em->persist($p1);
        $em->flush();
            //        $conn->insert('posts', ['id'=> 3, 'user_id' => '33', 'title' => 'title', 'content' => 'content', 'extra' => 'extra', '']);
    }
}
