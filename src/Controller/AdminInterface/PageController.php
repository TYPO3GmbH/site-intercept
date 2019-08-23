<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller handling content pages
 *
 * @Route("/page", defaults={"_format"="html"})
 */
class PageController extends AbstractController
{
    /**
     * @Route("/privacy-policy", methods={"GET"}, name="admin_page_privacy")
     * @return Response
     */
    public function privacy(): Response
    {
        return $this->render('page/privacy.html.twig');
    }

    /**
     * @Route("/legal", methods={"GET"}, name="admin_page_legal")
     * @return Response
     */
    public function legal(): Response
    {
        return $this->render('page/legal.html.twig');
    }
}
