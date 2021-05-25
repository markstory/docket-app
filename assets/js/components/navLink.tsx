import {InertiaLink, usePage} from '@inertiajs/inertia-react';
import classnames from 'classnames';

type LinkProps = React.ComponentProps<typeof InertiaLink>;

type Props = React.PropsWithChildren<{
  href: LinkProps['href'];
  className?: string;
}>;

function NavLink({children, className, href}: Props) {
  const page = usePage();
  className = classnames(className ?? '', {
    active: page.url.indexOf(href) > 0,
  });

  return (
    <InertiaLink href={href} className={className}>
      {children}
    </InertiaLink>
  );
}
export default NavLink;
