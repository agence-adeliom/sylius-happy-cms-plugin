- Override sylius home page to get the root cms page

```yaml
sylius_shop_homepage:
  path: /{_locale}/
  methods: [GET]
  controller: App\Controller\HomepageController::indexAction
```

- in App\Controller\HomepageController :

```php
public function indexAction(Request $request): Response
    {
        /** @var ?PageRepositoryInterface $pageRepository */
        $pageRepository = $this->entityManager->getRepository(PageInterface::class);
        if ($pageRepository instanceof PageRepositoryInterface) {
            $page = $pageRepository
                ->getHomePage($request->getLocale());
            if (null !== $page) {
                $onlineRoute = $page->getOnlineRoute();
                if ($onlineRoute instanceof RouteInterface) {
                    return $this->routeRenderService->renderAction(
                        $page,
                        $request,
                        $onlineRoute,
                    );
                }
            }
        }
        return new Response('', Response::HTTP_NOT_FOUND);
    }
```
