ABOUT

"Eve" is short for "Entity Views Embedder;" it provides a Views display plugin
that allows the output of a View to be embedded in the content of any Drupal
entity. The body of a node or comment, the profile of a user account, or the
listing page for a Taxonomy term are all examples of entity content.

The placement of the view in the entity's content can be reordered on the "Field
Display" administration page for that entity, like other fields added using the
Field UI module.

In addition, the unique ID of the entity the view is embedded in -- as well as
any tokens generated from that entity -- can be passed in as arguments to the view.
For example, you might make a View that displays posts with an 'Author ID' argument,
then use Eve to attach the view to the User entity type. When a user profile is
displayed, the User's ID will be passed in as the argument to the view magically.

That's right: magically.

Eve is powered by witchcraft.

HISTORY

Eve was originally developed by Jeff Eaton but never released. Larry
Garfield later cleaned it up and added the CCK integration, then released it
under the name 'Views Attach.' Endless confusion followed, as everyone thought
it would allow them to attach things to Views. Then Jeff Eaton refactored it to
use Drupal 7's Entity API. Then they renamed it again, because they didn't want
to write an upgrade path.

REQUIREMENTS

- Drupal 7
- Views 3

AUTHOR AND CREDIT

Original Development:
Jeff Eaton "eaton" (http://drupal.org/user/16496)

Maintainer:
Larry Garfield "Crell" (http://drupal.org/user/26398)
