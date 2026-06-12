import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

export function ProtectedRoute() {
  const { isAuthenticated, loading } = useAuth()
  if (loading) return <div className="page-loading">Loading...</div>
  return isAuthenticated ? <Outlet /> : <Navigate to="/login" replace />
}

export function AdminRoute() {
  const { isAuthenticated, isAdmin, loading } = useAuth()
  if (loading) return <div className="page-loading">Loading...</div>
  if (!isAuthenticated) return <Navigate to="/login" replace />
  return isAdmin ? <Outlet /> : <Navigate to="/dashboard" replace />
}

export function GuestRoute() {
  const { isAuthenticated, loading } = useAuth()
  if (loading) return <div className="page-loading">Loading...</div>
  return isAuthenticated ? <Navigate to="/dashboard" replace /> : <Outlet />
}
