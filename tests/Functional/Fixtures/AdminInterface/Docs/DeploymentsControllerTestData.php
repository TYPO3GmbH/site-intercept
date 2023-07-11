<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\Fixtures\AdminInterface\Docs;

use App\Entity\DocumentationJar;
use App\Enum\DocumentationStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DeploymentsControllerTestData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Home page
        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/TYPO3-Documentation/DocsTypo3Org-Homepage.git')
            ->setVendor('typo3')
            ->setName('docs-homepage')
            ->setPackageName('typo3/docs-homepage')
            ->setPackageType('typo3-cms-documentation')
            ->setExtensionKey('docs_that_is_no_extension')
            ->setBranch('main')
            ->setTargetBranchDirectory('main')
            ->setTypeLong('docs-home')
            ->setTypeShort('h')
            ->setStatus(DocumentationStatus::STATUS_RENDERED)
            ->setBuildKey('')
            ->setMaximumTypoVersion('')
            ->setMinimumTypoVersion('')
            ->setReRenderNeeded(false)
            ->setNew(false)
            ->setApproved(true)
            ->setPublicComposerJsonUrl('https://raw.githubusercontent.com/TYPO3-Documentation/DocsTypo3Org-Homepage/main/composer.json');
        $manager->persist($documentationJar);

        // Core extension
        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/TYPO3-CMS/felogin.git')
            ->setVendor('typo3')
            ->setName('cms-felogin')
            ->setPackageName('typo3/cms-felogin')
            ->setPackageType('typo3-cms-framework')
            ->setExtensionKey('felogin')
            ->setBranch('main')
            ->setTargetBranchDirectory('main')
            ->setTypeLong('core-extension')
            ->setTypeShort('c')
            ->setStatus(DocumentationStatus::STATUS_RENDERED)
            ->setBuildKey('')
            ->setMaximumTypoVersion('')
            ->setMinimumTypoVersion('')
            ->setReRenderNeeded(false)
            ->setNew(false)
            ->setApproved(true)
            ->setPublicComposerJsonUrl('');
        $manager->persist($documentationJar);

        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/TYPO3-CMS/felogin.git')
            ->setVendor('typo3')
            ->setName('cms-felogin')
            ->setPackageName('typo3/cms-felogin')
            ->setPackageType('typo3-cms-framework')
            ->setExtensionKey('felogin')
            ->setBranch('draft')
            ->setTargetBranchDirectory('draft')
            ->setTypeLong('core-extension')
            ->setTypeShort('c')
            ->setStatus(DocumentationStatus::STATUS_RENDERED)
            ->setBuildKey('')
            ->setMaximumTypoVersion('')
            ->setMinimumTypoVersion('')
            ->setReRenderNeeded(false)
            ->setNew(false)
            ->setApproved(true)
            ->setPublicComposerJsonUrl('');
        $manager->persist($documentationJar);

        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/TYPO3-CMS/felogin.git')
            ->setVendor('typo3')
            ->setName('cms-felogin')
            ->setPackageName('typo3/cms-felogin')
            ->setPackageType('typo3-cms-framework')
            ->setExtensionKey('felogin')
            ->setBranch('9.5')
            ->setTargetBranchDirectory('9.5')
            ->setTypeLong('core-extension')
            ->setTypeShort('c')
            ->setStatus(DocumentationStatus::STATUS_RENDERED)
            ->setBuildKey('')
            ->setMaximumTypoVersion('')
            ->setMinimumTypoVersion('')
            ->setReRenderNeeded(false)
            ->setNew(false)
            ->setApproved(true)
            ->setPublicComposerJsonUrl('');
        $manager->persist($documentationJar);

        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/TYPO3-CMS/felogin.git')
            ->setVendor('typo3')
            ->setName('cms-felogin')
            ->setPackageName('typo3/cms-felogin')
            ->setPackageType('typo3-cms-framework')
            ->setExtensionKey('felogin')
            ->setBranch('8.7')
            ->setTargetBranchDirectory('8.7')
            ->setTypeLong('core-extension')
            ->setTypeShort('c')
            ->setStatus(DocumentationStatus::STATUS_RENDERING)
            ->setBuildKey('')
            ->setMaximumTypoVersion('')
            ->setMinimumTypoVersion('')
            ->setReRenderNeeded(false)
            ->setNew(false)
            ->setApproved(true)
            ->setPublicComposerJsonUrl('');
        $manager->persist($documentationJar);

        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/TYPO3-CMS/felogin.git')
            ->setVendor('typo3')
            ->setName('cms-felogin')
            ->setPackageName('typo3/cms-felogin')
            ->setPackageType('typo3-cms-framework')
            ->setExtensionKey('felogin')
            ->setBranch('7.6')
            ->setTargetBranchDirectory('7.6')
            ->setTypeLong('core-extension')
            ->setTypeShort('c')
            ->setStatus(DocumentationStatus::STATUS_DELETING)
            ->setBuildKey('')
            ->setMaximumTypoVersion('')
            ->setMinimumTypoVersion('')
            ->setReRenderNeeded(false)
            ->setNew(false)
            ->setApproved(true)
            ->setPublicComposerJsonUrl('');
        $manager->persist($documentationJar);

        // Community extension
        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/TYPO3GmbH/blog.git')
            ->setVendor('t3g')
            ->setName('blog')
            ->setPackageName('t3g/blog')
            ->setPackageType('typo3-cms-extension')
            ->setExtensionKey('blog')
            ->setBranch('main')
            ->setTargetBranchDirectory('main')
            ->setTypeLong('extension')
            ->setTypeShort('p')
            ->setStatus(DocumentationStatus::STATUS_RENDERED)
            ->setBuildKey('')
            ->setMinimumTypoVersion('9.5')
            ->setMaximumTypoVersion('9.5')
            ->setReRenderNeeded(false)
            ->setNew(false)
            ->setApproved(true)
            ->setPublicComposerJsonUrl('');
        $manager->persist($documentationJar);

        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/TYPO3GmbH/blog.git')
            ->setVendor('t3g')
            ->setName('blog')
            ->setPackageName('t3g/blog')
            ->setPackageType('typo3-cms-extension')
            ->setExtensionKey('blog')
            ->setBranch('draft')
            ->setTargetBranchDirectory('draft')
            ->setTypeLong('extension')
            ->setTypeShort('p')
            ->setStatus(DocumentationStatus::STATUS_RENDERED)
            ->setBuildKey('')
            ->setMinimumTypoVersion('9.5')
            ->setMaximumTypoVersion('9.5')
            ->setReRenderNeeded(false)
            ->setNew(false)
            ->setApproved(true)
            ->setPublicComposerJsonUrl('');
        $manager->persist($documentationJar);

        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/TYPO3GmbH/blog.git')
            ->setVendor('t3g')
            ->setName('blog')
            ->setPackageName('t3g/blog')
            ->setPackageType('typo3-cms-extension')
            ->setExtensionKey('blog')
            ->setBranch('8.7.4')
            ->setTargetBranchDirectory('8.7')
            ->setTypeLong('extension')
            ->setTypeShort('p')
            ->setStatus(DocumentationStatus::STATUS_RENDERED)
            ->setBuildKey('')
            ->setMinimumTypoVersion('9.5')
            ->setMaximumTypoVersion('9.5')
            ->setReRenderNeeded(false)
            ->setNew(false)
            ->setApproved(true)
            ->setPublicComposerJsonUrl('');
        $manager->persist($documentationJar);

        // Community extension (main only)
        $documentationJar = (new DocumentationJar())
            ->setRepositoryUrl('https://github.com/georgringer/news.git')
            ->setVendor('georgringer')
            ->setName('news')
            ->setPackageName('georgringer/news')
            ->setPackageType('typo3-cms-extension')
            ->setExtensionKey('news')
            ->setBranch('main')
            ->setTargetBranchDirectory('main')
            ->setTypeLong('extension')
            ->setTypeShort('p')
            ->setStatus(DocumentationStatus::STATUS_AWAITING_APPROVAL)
            ->setBuildKey('')
            ->setMinimumTypoVersion('9.5')
            ->setMaximumTypoVersion('9.5')
            ->setReRenderNeeded(false)
            ->setNew(true)
            ->setApproved(false)
            ->setPublicComposerJsonUrl('');
        $manager->persist($documentationJar);
        $manager->flush();
    }

    /**
     * Get the order of this fixture.
     */
    public function getOrder(): int
    {
        return 1;
    }
}
