<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Backend\View\BackendLayout\Grid;

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Language Column
 *
 * Object representation of a site language selected in the "page" module
 * to show translations of content elements.
 *
 * Contains getter methods to return various values associated with a single
 * language, e.g. localized page title, associated SiteLanguage instance,
 * edit URLs and link titles and so on.
 *
 * Stores a duplicated Grid object associated with the SiteLanguage.
 *
 * Accessed from Fluid templates - generated from within BackendLayout when
 * "page" module is in "languages" mode.
 *
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class LanguageColumn extends AbstractGridObject
{
    protected readonly array $localizationConfiguration;

    public function __construct(
        protected PageLayoutContext $context,
        protected readonly Grid $grid,
        protected readonly array $translationInfo
    ) {
        parent::__construct($context);
        $this->localizationConfiguration = BackendUtility::getPagesTSconfig($context->getPageId())['mod.']['web_layout.']['localization.'] ?? [];
    }

    public function getGrid(): Grid
    {
        return $this->grid;
    }

    public function getPageIcon(): string
    {
        $localizedPageRecord = $this->context->getLocalizedPageRecord() ?? $this->context->getPageRecord();
        return BackendUtility::wrapClickMenuOnIcon(
            $this->iconFactory->getIconForRecord('pages', $localizedPageRecord, IconSize::SMALL)->render(),
            'pages',
            $localizedPageRecord['uid']
        );
    }

    public function getAllowTranslate(): bool
    {
        return ($this->localizationConfiguration['enableTranslate'] ?? true) && !($this->getTranslationData()['hasStandAloneContent'] ?? false);
    }

    public function getTranslationData(): array
    {
        return $this->translationInfo;
    }

    public function getAllowTranslateCopy(): bool
    {
        return ($this->localizationConfiguration['enableCopy'] ?? true) && !($this->getTranslationData()['hasTranslations'] ?? false);
    }

    public function getTranslatePageTitle(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newPageContent_translate');
    }

    public function getAllowEditPage(): bool
    {
        return $this->getBackendUser()->check('tables_modify', 'pages')
            && $this->getBackendUser()->checkLanguageAccess($this->context->getSiteLanguage()->getLanguageId());
    }

    public function getPageEditTitle(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:edit');
    }

    public function getPageEditUrl(): string
    {
        $pageRecordUid = $this->context->getLocalizedPageRecord()['uid'] ?? $this->context->getPageRecord()['uid'];
        $urlParameters = [
            'edit' => [
                'pages' => [
                    $pageRecordUid => 'edit',
                ],
            ],
            'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
        ];
        // Disallow manual adjustment of the language field for pages
        if (($languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? '') !== '') {
            $urlParameters['overrideVals']['pages'][$languageField] = $this->context->getSiteLanguage()->getLanguageId();
        }
        return (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', $urlParameters);
    }

    public function getAllowViewPage(): bool
    {
        return VersionState::tryFrom($this->context->getPageRecord()['t3ver_state'] ?? 0) !== VersionState::DELETE_PLACEHOLDER;
    }

    public function getViewPageLinkTitle(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage');
    }

    public function getPreviewUrlAttributes(): string
    {
        $pageId = $this->context->getPageId();
        $languageId = $this->context->getSiteLanguage()->getLanguageId();
        return (string)PreviewUriBuilder::create($pageId)
            ->withRootLine(BackendUtility::BEgetRootLine($pageId))
            ->withLanguage($languageId)
            ->serializeDispatcherAttributes();
    }
}
