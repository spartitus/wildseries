<?php
// src/Controller/WildController.php
namespace App\Controller;

use App\Entity\Actor;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Episode;
use App\Entity\Program;
use App\Entity\Season;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\ProgramSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/wild", name="wild_")
 */

class WildController extends AbstractController
{

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
     * @Route("/episode/{id}", name="show_episode")
     * @param Episode $episode
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function showByEpisode(Episode $episode, Request $request, EntityManagerInterface $em): Response
    {
        $season = $episode->getSeason();
        $program = $season->getProgram();
        $comments = $episode->getComments();

        $comment = new Comment();
        $form  = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $author = $this->getUser();
            $comment->setEpisode($episode);
            $comment->setAuthor($author);
            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('wild_show_episode', ['id' => $episode->getId()]);
        }

        return $this->render('wild/episode.html.twig', [
            'episode' => $episode,
            'program' => $program,
            'season'  =>$season,
            'comments' => $comments,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{comment}", name="comment_delete", methods={"DELETE"})
     * @param Request $request
     * @param Comment $comment
     * @return Response
     */
    public function deleteCommentUser(Request $request, Comment $comment): Response
    {
        $episode = $comment->getEpisode();
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
        }
        return $this->redirectToRoute('wild_show_episode', ['id' => $episode->getId()]);
    }

    /**
     * @Route("/actor/{name}", name="actor")
     * @param Actor $actor
     * @return Response
     */
    public function showActor(Actor $actor): Response
    {
        $programs = $actor->getPrograms();
        return $this->render('wild/actor.html.twig', [
            'actor' => $actor,
            'programs' => $programs,
        ]);
    }
}

