<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\Fixtures\AdminInterface\Docs;

use App\Entity\DocsServerRedirect;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RedirectControllerTestData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $redirect = (new DocsServerRedirect())
            ->setId(1)
            ->setSource('/p/vendor/packageOld/1.0/Foo.html')
            ->setTarget('/p/vendor/packageNew/1.0/Foo.html');
        $manager->persist($redirect);
        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return int
     */
    public function getOrder()
    {
        return 1;
    }
}
