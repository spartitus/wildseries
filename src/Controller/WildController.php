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
 * @Route("/wild", name="wild_")
 */

class WildController extends AbstractController
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


    /**
     * Show all rows from Program’s entity
     *
     * @Route("", name="index")
     */
    public function index(): Response
    {
        $programs = $this->getDoctrine()
            ->getRepository(Program::class)
            ->findAll();
        if (!$programs) {
            throw $this->createNotFoundException(
                'No program found in program\'s table.'
            );
        }
        return $this->render(
            'wild/index.html.twig',
            ['programs' => $programs]
        );
    }
    /**
     * Getting a program with a formatted slug for title
     *
     * @param string $slug The slugger
     * @Route("/show/{slug<^[ a-zA-Z0-9-é]+$>}", defaults={"slug" = null}, name="show")
     * @return Response
     */
    public function showByProgram(string $slug):Response
    {
        if (!$slug) {
            throw $this
                ->createNotFoundException('No slug has been sent to find a program in program\'s table.');
        }
        $slug = preg_replace(
            '/-/',
            ' ', ucwords(trim(strip_tags($slug)), "-")
        );
        $program = $this->getDoctrine()
            ->getRepository(Program::class)
            ->findOneBy([
                'title' => mb_strtolower($slug)
            ]);
        if (!$program) {
            throw $this->createNotFoundException(
                'No program with '.$slug.' title, found in program\'s table.'
            );
        }
        $seasons = $this->getDoctrine()
            ->getRepository(Season::class)
            ->findBy([
                'program' => $program,
            ]);
        return $this->render('wild/show.html.twig', [
            'program' => $program,
            'slug'    => $slug,
            'seasons' => $seasons,
        ]);
    }
    /**
     * @Route("/category/{categoryName}", requirements={"categoryName" = "^[a-z0-9]+(?:-[a-z0-9]+)*$"}, name="show_category")
     * @param string $categoryName
     * @return Response
     */
    public function showByCategory(string $categoryName): Response
    {
        if (!$categoryName) {
            throw $this->createNotFoundException('No category has been sent to find a category in category\'s table.');
        }
        $categoryName = preg_replace(
            '/-/',
            ' ', ucwords(trim(strip_tags($categoryName)), "-")
        );
        $category = $this->getDoctrine()
            ->getRepository(Category::class)
            ->findOneBy(['name' => mb_strtolower($categoryName)]);
        if (!$category) {
            throw $this->createNotFoundException('No category' . $categoryName . 'found in category\'s table.');
        }
        $programs = $this->getDoctrine()
            ->getRepository(Program::class)
            ->findBy(['category' =>$category], ['id' => 'ASC']);

        if (!$programs) {
            throw $this->createNotFoundException('No program found in program\'s table.');
        }
        return $this->render('wild/category.html.twig', [
            'programs' => $programs,
            'category' => $category,
        ]);
    }

    /**
     * @Route("/season/{id}", defaults={"id" = null}, name="show_season")
     * @param int $id
     * @return Response
     */
    public function showBySeason(int $id): Response
    {
        if (!$id) {
            throw $this->createNotFoundException('No season has been find in season\'s table.');
        }
        $season = $this->getDoctrine()
            ->getRepository(Season::class)
            ->find($id);
        $program = $season->getProgram();
        $episodes = $season->getEpisodes();
        if(!$season) {
            throw $this->createNotFoundException('No season found with '.$id.' in season\'s table.');
        }
        return $this->render('wild/season.html.twig', [
            'program' => $program,
            'season' => $season,
            'episodes' => $episodes,
        ]);
    }
    /**
     * @Route("/episode/{episode}", name="episode")
     */
    public function showEpisode(Episode $episode)
    {
        $program = $episode->getSeason()->getProgram();
        return $this->render('wild/episode.html.twig', [
            'episode' => $episode,
            'program' => $program,
        ]);

        //TODO render in twig to show episode data. access to program name in twig with episode.season.program.title
    }

}

