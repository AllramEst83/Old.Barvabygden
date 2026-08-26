# Existing archive baseline

## Display flow
- `index.html` has `#archiveAccordion` placeholder in section `#archive`.
- Runtime loader fetches `./assets/pages/newsletter.html`.
- Therefore all archive item edits must happen in `assets/pages/newsletter.html`.

## Already-migrated year groups in newsletter
- 2010: issue 36 (html + pdf links)
- 2016: issues 47, 48 (html + pdf links)
- 2017: issues 49, 50 (html + pdf links)
- 2018: issues 51, 52 (html + pdf links)
- 2019: issues 53, 54 (pdf links only)

## Local files currently present under assets/barva-news
- `assets/barva-news/2010/barva-news-2010-issue-36.htm`
- `assets/barva-news/2016/barva-news-2016-issue-47.htm`
- `assets/barva-news/2016/barva-news-2016-issue-48.htm`
- `assets/barva-news/2017/barva-news-2017-issue-49.htm`
- `assets/barva-news/2017/barva-news-2017-issue-50.htm`
- `assets/barva-news/2018/barva-news-2018-issue-51.htm`
- `assets/barva-news/2018/barva-news-2018-issue-52.htm`

## Notable mismatch to resolve during migration
- `assets/pages/newsletter.html` contains multiple PDF links, but no local `.pdf` files were found in the repo at inventory time.
- Root-level legacy issue files (`bb-*.htm`) are present and should be matched/moved into `assets/barva-news/<year>/`.
