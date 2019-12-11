<?php
// src/Controller/WildController.php
namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Program;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\CategoryType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/category", name="category_")
 */

class CategoryController extends AbstractController
{
    /**
     * Forms
     * @Route("/add", name="add", methods={"GET", "POST"})
     * @return Response
     */
    public function new(EntityManagerInterface $em, Request $request)
    {
        $category = new Category();
        $category->setName('Sweet Opera');
        $form = $this->createForm(CategoryType::class, $category);
        $form-> handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($category);
            $em->flush();
            return $this->redirectToRoute('wild_add');
        }
        return $this->render(
            'wild/add.html.twig',
            ['form' => $form->createView(),]
        );
    }

}

