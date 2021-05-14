describe('My First Test', () => {
  it('Visits the Kitchen Sink', () => {
    cy.visit('http://web')
  })
})

describe('Crawl the entire website', () => {
  // TODO: Query routers from Drupal.
  ;['mouseover', 'mouseout', 'mouseenter', 'mouseleave'].forEach((event) => {
    it('triggers event: ' + event, () => {
      // if your app uses jQuery, then we can trigger a jQuery
      // event that causes the event callback to fire
      cy.get('#with-jquery')
        .invoke('trigger', event)
        .get('#messages')
        .should('contain', 'the event ' + event + 'was fired')
    })
  })
})
