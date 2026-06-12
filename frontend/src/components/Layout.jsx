import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export default function Layout({ title, children }) {
  const { user, isAdmin, logout } = useAuth()
  const location = useLocation()

  return (
    <div className="app-layout">
      <header className="app-header">
        <h1>{title}</h1>
        <nav className="app-nav">
          {isAdmin && (
            <Link to="/admin" className={location.pathname === '/admin' ? 'active' : ''}>Admin</Link>
          )}
          <Link to="/dashboard" className={location.pathname === '/dashboard' ? 'active' : ''}>Dashboard</Link>
          <Link to="/profile" className={location.pathname === '/profile' ? 'active' : ''}>Profile</Link>
          <span className="user-name">{user?.name} ({user?.role})</span>
          <button onClick={logout} className="btn btn-danger btn-sm">Logout</button>
        </nav>
      </header>
      <main className="app-content">{children}</main>
    </div>
  )
}
