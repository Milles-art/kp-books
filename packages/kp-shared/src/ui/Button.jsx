import { forwardRef } from 'react';

const variants = {
  primary: 'btn',
  green: 'btn green',
  ghost: 'btn ghost',
  sm: 'btn sm',
};

export const Button = forwardRef(function Button({ variant = 'primary', size, className = '', children, ...props }, ref) {
  const cls = [variants[variant] || 'btn', size === 'sm' ? 'sm' : '', className].filter(Boolean).join(' ');
  return <button ref={ref} className={cls} {...props}>{children}</button>;
});

export default Button;
