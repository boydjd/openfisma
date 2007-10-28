class DashboardController < ApplicationController

  def index
    @page_title = "Dashboard"
    @time = Time.now
  end

end
