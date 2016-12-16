<?php

namespace AppBundle\Controller;
use AppBundle\AppBundle;
use AppBundle\Entity\Blog;
use AppBundle\Entity\Comment;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Tests\Fixtures\Entity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BlogController extends Controller
{
    /**
     * @Route("/blog", name="blog")
     */
    public function blogAction()
    {
        $blogs=$this->getDoctrine()
            ->getRepository('AppBundle:Blog')
            ->findBy([],array('id' => 'DESC'));

        //find columnName
        /*$em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT p.description
              FROM AppBundle:Blog p'
            );
        $products = $query->getResult();
        var_dump($products);*/
        // replace this example code with whatever you need
        return $this->render('template/allblog.html.twig',array(
            'blogs'=>$blogs
        ));

    }
    /**
     * @Route("/blog/show/{id}", name="show")
     */
    public function showAction(Request $request,$id)
    {
        $blog=$this->getDoctrine()
            ->getRepository('AppBundle:Blog')
            ->find($id);
        
        $comment = new Comment();
        $form = $this->createFormBuilder($comment)
            ->add('comment',TextareaType::class,array('attr'=>array('class'=>'form-control','style'=>'margin-bottom:15px;','placeholder'=>'write a comment'),'label'=>false))
            ->add('save',SubmitType::class,array('label'=>'Add comment','attr'=>array('class'=>'btn btn-primary','style'=>'margin-bottom:15px')))
            ->getForm();
        $form->handleRequest($request);
        /////////////////////////////////////////////////////
        if($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $commenttext = $form['comment']->getData();
            $comment->setComment($commenttext);
            $userName = $this->getUser();
            $comment->setBlog($blog);
            if(isset($userName)){
                $userName = $this->getUser();
            }
            else{
                $userName = 'no user';
            }
            $comment->setUser($userName);
            $em->persist($comment);
            $em->flush();
        }
        $comment=$this->getDoctrine()
            ->getRepository('AppBundle:Comment')
            ->findBy(array('blog'=>$id));

        return $this->render('template/blogsingle.html.twig', array(
            'blog'=>$blog,
            'comment'=>$comment,
            'form'=>$form->createView()
        ));
    }
    /**
     * @Route("/blog/add", name="add")
     */
    public function addAction(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {

            echo "User loged".'('.$this->getUser().')';
            $blog = new Blog();
            $comment = new Comment();

            $form = $this->createFormBuilder($blog)
            ->add('name',TextType::class,array('attr'=>array('class'=>'form-control','style'=>'margin-bottom:15px')))
            ->add('category',TextType::class,array('attr'=>array('class'=>'form-control','style'=>'margin-bottom:15px')))
            ->add('description',TextareaType::class,array('attr'=>array('class'=>'form-control','style'=>'margin-bottom:15px')))
            ->add('priority',ChoiceType::class,array('choices'=>array('Low'=>'Low','Normal'=>'Normal','High'=>'High'),'attr'=>array('class'=>'form-control','style'=>'margin-bottom:15px')))
            ->add('due_date',DateTimeType::class,array('attr'=>array('class'=>'formcontrol','style'=>'margin-bottom:15px;width:500px')))
            ->add('save',SubmitType::class,array('label'=>'Add Blog','attr'=>array('class'=>'btn btn-primary','style'=>'margin-bottom:15px')))
            ->add('file')

            ->getForm();

        $form->handleRequest($request);

        if($request->isMethod('POST') && $form->isValid()){

            //die('SUBMITTED');
            $name = $form['name']->getData();
            $category = $form['category']->getData();
            $description = $form['description']->getData();
            $priority = $form['priority']->getData();
            $due_date = $form['due_date']->getData();

            $now = new\DateTime('now');
            $userName = $this->getUser();

            $blog->setName($name);
            $blog->setCategory($category);
            $blog->setDescription($description);
            $blog->setPriority($priority);
            $blog->setDueDate($due_date);
            $blog->setCreateDate($now);
            $blog->setUsername($userName);
            $blog->upload();

            $comment->setUser($this->getUser());
            $comment->setBlog($blog);
            $comment->setComment('');

            $em = $this->getDoctrine()->getManager();
            $em->persist($blog);
            $em->persist($comment);
            $em->flush();
            $this->addFlash('notice','Blog Added');
            return $this->redirectToRoute('blog');
        }
        // replace this example code with whatever you need
            return $this->render('template/addblog.html.twig',array(
            'form'=>$form->createView()
             ));
        }
        else{
          //return  new  Response('No user,please logged');
            return $this->redirectToRoute('fos_user_security_login');
        }
    }

    /**
     * @Route("/blog/edit/{id}", name="edit")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function editAction($id,Request $request)
    {
        $blog = $this->getDoctrine()->
        getRepository('AppBundle:Blog')
            ->find($id);

        $blog->setName($blog->getName());
        $blog->setCategory($blog->getCategory());
        $blog->setDescription($blog->getDescription());
        $blog->setPriority($blog->getPriority());
        $blog->setDueDate($blog->getDueDate());
        $blog->setcreate_date($blog->getCreateDate());
        //$blog->setFile($blog->getFile());

        $form = $this->createFormBuilder($blog)
            ->add('name',TextType::class,array('attr'=>array('class'=>'form-control','style'=>'margin-bottom:15px')))
            ->add('category',TextType::class,array('attr'=>array('class'=>'form-control','style'=>'margin-bottom:15px')))
            ->add('description',TextareaType::class,array('attr'=>array('class'=>'form-control','style'=>'margin-bottom:15px')))
            ->add('priority',ChoiceType::class,array('choices'=>array('Low'=>'Low','Normal'=>'Normal','High'=>'High'),'attr'=>array('class'=>'form-control','style'=>'margin-bottom:15px')))
            ->add('due_date',DateTimeType::class,array('attr'=>array('class'=>'formcontrol','style'=>'margin-bottom:15px;width:500px')))
            ->add('save',SubmitType::class,array('label'=>'Update Blog','attr'=>array('class'=>'btn btn-primary','style'=>'margin-bottom:15px')))
            ->add('file')

            ->getForm();
        $form->handleRequest($request);

           // var_dump($this->getUser());
        if($form->isSubmitted() && $form->isValid() ){
            //die('SUBMITTED');
            //get data

            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
            'SELECT p.username
              FROM AppBundle:Blog p WHERE p.id=:id'
            )->setParameter('id',$id);


            $username = $query->getResult();
            foreach($username as $value)
            {
                $user_login = $value['username'];
            }
            if($this->getUser()!= $user_login){
               /* $this->addFlash('notice','Error!');
               // return new Response("access denied for user");
                throw $this->createNotFoundException('Access denied for user!!!!');
                //throw new AccessDeniedException('Access denied for user!!!!');
                return $this->redirectToRoute('blog');*/

                $this->addFlash('error','Access denied for user');
                return $this->redirectToRoute('blog');
            }

            $name = $form['name']->getData();
            $category = $form['category']->getData();
            $description = $form['description']->getData();
            $priority = $form['priority']->getData();
            $due_date = $form['due_date']->getData();
            //$file = $form['path']->getData();
            $now = new\DateTime('now');

            $em = $this->getDoctrine()->getManager();
            $blog = $em->getRepository('AppBundle:Blog')->find($id);

            $blog->setName($name);
            $blog->setCategory($category);
            $blog->setDescription($description);
            $blog->setPriority($priority);
            $blog->setDueDate($due_date);
            $blog->setcreate_date($now);
            //$blog->setFile($file);
            $blog->upload();
            $em->flush();
            $this->addFlash('notice','Blog Updated');
            return $this->redirectToRoute('blog');
        }
        // replace this example code with whatever you need
        return $this->render('template/edit.html.twig',array(
            'blog'=>$blog,
            'form'=>$form->createView()
        ));
    }
    /**
     * @Route("blog/delete/{id}", name="delete")
     */
    public function deleteAction($id,Request $request)
    {
        $blog=$this->getDoctrine()
            ->getRepository('AppBundle:Blog')
            ->find($id);
        $img_path = $blog->getPath();
        //$blo_one = new Blog();
        if($img_path){
            if (file_exists($file =__DIR__.'/../../../web/uploads/img/'.$img_path )) {
                unlink($file);
            }
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($blog);
        $em->flush();

        $this->addFlash('notice','Blog removed');
        return $this->redirectToRoute('blog');
    }

}